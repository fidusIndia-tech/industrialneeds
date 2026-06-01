<?php

namespace App\CPU;

use App\Model\Brand;
use App\Model\Category;
use App\Model\ProductImportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Chunked engine for the background product bulk import.
 *
 * The preview step streams cleaned, ready-to-insert rows to a temp NDJSON file (one product per
 * line). This processor consumes that file in chunks so no single request runs long enough to
 * time out. After every chunk the ProductImportJob counters and processed_rows are updated, so
 * progress is durable across page refreshes.
 *
 * IMPORTANT (per current requirements): images are NOT processed here. No URLs are downloaded and
 * no ZIP files are read — every product gets the default placeholder. Image support can be layered
 * back on later. Category/brand resolution is cached within each chunk; new records created in an
 * earlier chunk are simply re-read from the DB at the start of the next chunk.
 */
class BulkImportProcessor
{
    /**
     * Process the next chunk of $chunkSize rows for the given job. Updates the job in place.
     * Throws only on a fatal error (e.g. missing data file or a chunk-level DB failure); a single
     * bad row is caught, recorded in failed_rows and counted, and processing continues.
     */
    public static function processChunk(ProductImportJob $job, int $chunkSize = 200): void
    {
        $abs = Storage::disk('local')->path($job->file_path);
        if (!is_file($abs)) {
            throw new \RuntimeException('Import data file is missing (it may have been cleaned up).');
        }

        // Resolution caches (rebuilt per chunk; cheap relative to a few hundred row inserts).
        $brandMap = BulkImportHelper::buildBrandMap();
        $catMap   = BulkImportHelper::buildCategoryMap();
        $validIds = [
            0 => Category::where('position', 0)->pluck('id')->map(fn ($i) => (int)$i)->toArray(),
            1 => Category::where('position', 1)->pluck('id')->map(fn ($i) => (int)$i)->toArray(),
            2 => Category::where('position', 2)->pluck('id')->map(fn ($i) => (int)$i)->toArray(),
        ];
        $usedSlugs  = [];
        $catNames   = Category::pluck('name', 'id')->toArray();
        $brandNames = Brand::pluck('name', 'id')->toArray();
        $catNameFor = function ($id, $fallback) use (&$catNames) {
            if ($id && isset($catNames[$id])) { return $catNames[$id]; }
            return ($fallback !== null && $fallback !== '') ? BulkImportHelper::displayName($fallback) : null;
        };

        // Per-chunk deltas (added to the persisted job counters at the end).
        $d = [
            'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0,
            'new_brands' => 0, 'new_categories' => 0, 'new_sub_categories' => 0, 'new_sub_sub_categories' => 0,
            'calc_purchase' => 0, 'calc_unit' => 0, 'default_unit' => 0, 'default_moq' => 0, 'default_stock' => 0,
            'with_images' => 0, 'without_images' => 0, 'images_from_zip' => 0,
            'images_downloaded' => 0, 'failed_downloads' => 0, 'invalid_images' => 0,
        ];
        $newFailures = [];

        // Optional image processing (URLs + uploaded ZIP). Controlled per job; off => placeholder only.
        $options = $job->import_options ?? [];
        $processImages = !empty($options['process_images']);
        $zip = null;
        $zipEntries = [];
        if ($processImages && !empty($options['zip_token'])) {
            $zipRel = 'bulk_import_zip/' . $options['zip_token'] . '.zip';
            if (Storage::disk('local')->exists($zipRel)) {
                $zip = new \ZipArchive();
                if ($zip->open(Storage::disk('local')->path($zipRel)) === true) {
                    $zipEntries = self::zipEntries($zip);
                } else {
                    $zip = null; // unreadable ZIP must not stop the import
                    Log::warning("Bulk import job {$job->id}: images ZIP could not be opened.");
                }
            }
        }

        $fh = fopen($abs, 'r');
        if (!$fh) {
            if ($zip) { $zip->close(); }
            throw new \RuntimeException('Could not open import data file.');
        }

        // Skip rows already processed in earlier chunks.
        $skip = (int)$job->processed_rows;
        for ($i = 0; $i < $skip; $i++) {
            if (fgets($fh) === false) { break; }
        }

        $processedThisChunk = 0;
        DB::beginTransaction();
        try {
            while ($processedThisChunk < $chunkSize && ($line = fgets($fh)) !== false) {
                $processedThisChunk++;
                $line = trim($line);
                if ($line === '') { continue; }
                $product = json_decode($line, true);
                if (!is_array($product)) {
                    $d['failed']++;
                    if (count($newFailures) < 50) { $newFailures[] = ['row' => '?', 'reason' => 'Corrupt import row.']; }
                    continue;
                }

                try {
                    // ---- brand (find-or-create, cached) ----
                    $brandId = !empty($product['brand']['id']) ? (int)$product['brand']['id'] : null;
                    if (!$brandId) {
                        $brandCreated = 0;
                        $brandId = BulkImportHelper::resolveBrandId($product['brand']['name'] ?? '', $brandMap, $brandCreated);
                        $d['new_brands'] += $brandCreated;
                    }
                    if (!$brandId) {
                        throw new \RuntimeException('Could not resolve brand.');
                    }

                    // ---- category path (find-or-create, cached) ----
                    $resolved = BulkImportHelper::resolveCategoryPath([
                        'category_id'           => $product['cat']['l1']['id'] ?? null,
                        'category_name'         => $product['cat']['l1']['name'] ?? null,
                        'sub_category_id'       => $product['cat']['l2']['id'] ?? null,
                        'sub_category_name'     => $product['cat']['l2']['name'] ?? null,
                        'sub_sub_category_id'   => $product['cat']['l3']['id'] ?? null,
                        'sub_sub_category_name' => $product['cat']['l3']['name'] ?? null,
                    ], $catMap, $validIds, $usedSlugs);
                    $d['new_categories']         += $resolved['created']['l1'];
                    $d['new_sub_categories']     += $resolved['created']['l2'];
                    $d['new_sub_sub_categories'] += $resolved['created']['l3'];

                    if (empty($resolved['category_ids'])) {
                        throw new \RuntimeException('Could not resolve category.');
                    }
                    foreach ($resolved['ids'] as $pos => $cid) {
                        if ($cid && !in_array($cid, $validIds[$pos], true)) {
                            $validIds[$pos][] = $cid;
                        }
                        if ($cid && !isset($catNames[$cid])) {
                            // make freshly-created names available for auto-details within this chunk
                            $catNames[$cid] = $catNameFor($cid, $product['cat']['l' . ($pos + 1)]['name'] ?? null);
                        }
                    }

                    // ---- auto-details when blank (no network) ----
                    $details = (string)($product['details'] ?? '');
                    if (trim($details) === '') {
                        $brandName = $brandNames[$brandId] ?? ($product['brand']['name'] ? BulkImportHelper::displayName($product['brand']['name']) : null);
                        $details = BulkImportHelper::autoDetails($brandName, $product['product_code'] ?? '', [
                            $catNameFor($resolved['ids'][0], $product['cat']['l1']['name'] ?? null),
                            $catNameFor($resolved['ids'][1], $product['cat']['l2']['name'] ?? null),
                            $catNameFor($resolved['ids'][2], $product['cat']['l3']['name'] ?? null),
                        ]);
                    }

                    // ---- images (optional): ZIP-named > URL > ZIP auto-match by code > placeholder ----
                    $thumbnail = 'def.png';
                    $gallery   = ['def.png'];
                    $hasImage  = false;
                    if ($processImages) {
                        $img = self::resolveImages($product, $zip, $zipEntries, $d);
                        $thumbnail = $img['thumbnail'];
                        $gallery   = $img['images'];
                        $hasImage  = $img['has_image'];
                    }

                    // ---- core attributes (prices -> USD, matching existing storage) ----
                    $attributes = [
                        'name'           => $product['name'],
                        'product_code'   => $product['product_code'] ?? null,
                        'category_ids'   => json_encode($resolved['category_ids']),
                        'brand_id'       => $brandId,
                        'unit'           => $product['unit'],
                        'min_qty'        => $product['min_qty'],
                        'refundable'     => $product['refundable'],
                        'unit_price'     => BackEndHelper::currency_to_usd($product['unit_price']),
                        'purchase_price' => BackEndHelper::currency_to_usd($product['purchase_price']),
                        'tax'            => $product['tax'],
                        'discount'       => $product['discount'],
                        'discount_type'  => $product['discount_type'],
                        'current_stock'  => $product['current_stock'],
                        'details'        => $details,
                        'meta_title'       => $product['meta_title'] ?? null,
                        'meta_description' => $product['meta_description'] ?? null,
                        'video_provider' => 'youtube',
                        'video_url'      => $product['youtube_video_url'] ?? '',
                        'updated_at'     => now(),
                    ];

                    if (($product['_action'] ?? 'create') === 'update' && !empty($product['_existing_id'])) {
                        // On update, only overwrite images when this run actually fetched some.
                        if ($hasImage) {
                            $attributes['thumbnail'] = $thumbnail;
                            $attributes['images']    = json_encode($gallery);
                        }
                        DB::table('products')->where('id', $product['_existing_id'])->update($attributes);
                        $d['updated']++;
                    } else {
                        // Image-pipeline state for new products: a placeholder is "queued" for the
                        // background fetcher; an image attached during import is "fetched".
                        $famBrand = $brandNames[$brandId] ?? ($product['brand']['name'] ?? null);
                        $fam = (new ProductImageFamilyService())->familyKey($famBrand, $product['product_code'] ?? '', $product['name'] ?? '');
                        $attributes['image_status']      = $hasImage ? 'fetched' : 'placeholder';
                        $attributes['image_source']      = $hasImage ? 'import' : null;
                        $attributes['image_family_key']  = $fam['key'];
                        $attributes['image_needs_review'] = false;

                        $attributes['slug']            = Str::slug($product['name'], '-') . '-' . Str::random(6);
                        $attributes['thumbnail']       = $thumbnail;
                        $attributes['images']          = json_encode($gallery);
                        $attributes['status']          = 1;
                        $attributes['request_status']  = 1;
                        $attributes['colors']          = json_encode([]);
                        $attributes['attributes']      = json_encode([]);
                        $attributes['choice_options']  = json_encode([]);
                        $attributes['variation']       = json_encode([]);
                        $attributes['featured_status'] = 1;
                        $attributes['added_by']        = 'admin';
                        $attributes['user_id']         = $job->admin_id;
                        $attributes['created_at']      = now();
                        DB::table('products')->insert($attributes);
                        $d['created']++;
                    }

                    if ($hasImage) { $d['with_images']++; } else { $d['without_images']++; }
                    if (!empty($product['flags']['calc_purchase'])) { $d['calc_purchase']++; }
                    if (!empty($product['flags']['calc_unit']))     { $d['calc_unit']++; }
                    if (!empty($product['flags']['default_unit']))  { $d['default_unit']++; }
                    if (!empty($product['flags']['default_moq']))   { $d['default_moq']++; }
                    if (!empty($product['flags']['default_stock'])) { $d['default_stock']++; }
                } catch (\Throwable $e) {
                    $d['failed']++;
                    if (count($newFailures) < 50) {
                        $newFailures[] = ['row' => $product['_row'] ?? '?', 'reason' => $e->getMessage()];
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($fh);
            if ($zip) { $zip->close(); }
            throw $e; // fatal chunk-level error; caller marks the job failed
        }
        fclose($fh);
        if ($zip) { $zip->close(); }

        // Persist progress + counters (one save per chunk — no per-row logging/writes).
        $job->processed_rows                  += $processedThisChunk;
        $job->created_count                   += $d['created'];
        $job->updated_count                   += $d['updated'];
        $job->skipped_count                   += $d['skipped'];
        $job->failed_count                    += $d['failed'];
        $job->new_brands_count                += $d['new_brands'];
        $job->new_categories_count            += $d['new_categories'];
        $job->new_sub_categories_count        += $d['new_sub_categories'];
        $job->new_sub_sub_categories_count    += $d['new_sub_sub_categories'];
        $job->calculated_purchase_price_count += $d['calc_purchase'];
        $job->calculated_selling_price_count  += $d['calc_unit'];
        $job->default_unit_used_count         += $d['default_unit'];
        $job->default_moq_used_count          += $d['default_moq'];
        $job->default_stock_used_count        += $d['default_stock'];
        $job->with_images_count               += $d['with_images'];
        $job->without_images_count            += $d['without_images'];
        $job->images_from_zip_count           += $d['images_from_zip'];
        $job->images_downloaded_count         += $d['images_downloaded'];
        $job->failed_image_downloads_count    += $d['failed_downloads'];
        $job->invalid_images_count            += $d['invalid_images'];

        if (!empty($newFailures)) {
            $existing = $job->failed_rows ?? [];
            $job->failed_rows = array_slice(array_merge($existing, $newFailures), 0, 500);
        }
        $job->save();

        Log::info("Bulk import job {$job->id}: chunk done, {$job->processed_rows}/{$job->total_rows} "
            . "(created {$job->created_count}, updated {$job->updated_count}, failed {$job->failed_count}).");
    }

    // ---------------------------------------------------------------------
    // Image resolution (ZIP matching + URL download + MIME validation).
    // Priority per slot: explicit ZIP file > URL > ZIP auto-match by code > placeholder.
    // Every candidate is MIME-validated (jpg/jpeg/png/webp only); failures never throw.
    // ---------------------------------------------------------------------

    /**
     * Index a ZipArchive's entries (skips directories + hidden/junk like __MACOSX, .DS_Store).
     * Each entry: ['name'=>raw, 'base'=>lowerBasename, 'stem'=>lowerStem, 'ext'=>lowerExt].
     */
    public static function zipEntries(\ZipArchive $zip): array
    {
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || substr($name, -1) === '/') {
                continue;
            }
            $base = basename(str_replace('\\', '/', $name));
            if ($base === '' || $base[0] === '.') {
                continue;
            }
            $entries[] = [
                'name' => $name,
                'base' => mb_strtolower($base),
                'stem' => mb_strtolower(pathinfo($base, PATHINFO_FILENAME)),
                'ext'  => strtolower(pathinfo($base, PATHINFO_EXTENSION)),
            ];
        }
        return $entries;
    }

    /** True if the ZIP has at least one image (thumbnail or gallery) for the given product code. */
    public static function zipHasImagesForCode(array $zipEntries, string $code): bool
    {
        $code = mb_strtolower(trim($code));
        if ($code === '') {
            return false;
        }
        return self::findByStem($zipEntries, $code) !== null || !empty(self::findGallery($zipEntries, $code));
    }

    /** Resolve a product's thumbnail + gallery filenames. Returns ['thumbnail','images','has_image']. */
    public static function resolveImages(array $product, ?\ZipArchive $zip, array $zipEntries, array &$d): array
    {
        $rowNo = $product['_row'] ?? '?';
        $code  = (string)($product['product_code'] ?? '');

        // --- thumbnail ---
        $thumbnail = 'def.png';
        $thumbFile = trim((string)($product['thumbnail_file'] ?? ''));
        if ($zip && $thumbFile !== '') {
            $stored = self::storeZipImage($zip, $zipEntries, $thumbFile, 'product/thumbnail/', $rowNo, $d);
            if ($stored) { $thumbnail = $stored; }
        }
        if ($thumbnail === 'def.png') {
            $thumbUrl = trim((string)($product['thumbnail_url'] ?? ''));
            if ($thumbUrl !== '') {
                $stored = self::storeUrlImage($thumbUrl, 'product/thumbnail/', $rowNo, $d);
                if ($stored) { $thumbnail = $stored; }
            }
        }
        if ($thumbnail === 'def.png' && $zip && $code !== '') {
            $entry = self::findByStem($zipEntries, mb_strtolower($code));
            if ($entry) {
                $stored = self::storeZipImage($zip, $zipEntries, $entry['base'], 'product/thumbnail/', $rowNo, $d);
                if ($stored) { $thumbnail = $stored; }
            }
        }

        // --- gallery ---
        $gallery = [];
        $galleryFiles = trim((string)($product['gallery_files'] ?? ''));
        if ($zip && $galleryFiles !== '') {
            foreach (explode(',', $galleryFiles) as $gf) {
                $gf = trim($gf);
                if ($gf === '') { continue; }
                $stored = self::storeZipImage($zip, $zipEntries, $gf, 'product/', $rowNo, $d);
                if ($stored) { $gallery[] = $stored; }
            }
        }
        if (empty($gallery)) {
            $galleryUrls = trim((string)($product['gallery_urls'] ?? ''));
            if ($galleryUrls !== '') {
                foreach (explode(',', $galleryUrls) as $url) {
                    $url = trim($url);
                    if ($url === '') { continue; }
                    $stored = self::storeUrlImage($url, 'product/', $rowNo, $d);
                    if ($stored) { $gallery[] = $stored; }
                }
            }
        }
        if (empty($gallery) && $zip && $code !== '') {
            foreach (self::findGallery($zipEntries, mb_strtolower($code)) as $entry) {
                $stored = self::storeZipImage($zip, $zipEntries, $entry['base'], 'product/', $rowNo, $d);
                if ($stored) { $gallery[] = $stored; }
            }
        }

        $hasImage = ($thumbnail !== 'def.png') || !empty($gallery);

        // Fallbacks: reuse the thumbnail as the gallery when only it exists, else placeholder.
        if (empty($gallery)) {
            if ($thumbnail !== 'def.png') {
                Storage::disk('public')->copy('product/thumbnail/' . $thumbnail, 'product/' . $thumbnail);
                $gallery = [$thumbnail];
            } else {
                $gallery = ['def.png'];
            }
        }

        return ['thumbnail' => $thumbnail, 'images' => $gallery, 'has_image' => $hasImage];
    }

    /** Find a ZIP entry whose stem equals $stem (any allowed image extension). */
    private static function findByStem(array $zipEntries, string $stem): ?array
    {
        foreach ($zipEntries as $e) {
            if ($e['stem'] === $stem && in_array($e['ext'], BulkImportHelper::ALLOWED_IMAGE_EXTENSIONS, true)) {
                return $e;
            }
        }
        return null;
    }

    /** Find gallery entries matching "<code>_<n>", ordered by n. */
    private static function findGallery(array $zipEntries, string $code): array
    {
        $matches = [];
        $prefix = $code . '_';
        foreach ($zipEntries as $e) {
            if (!in_array($e['ext'], BulkImportHelper::ALLOWED_IMAGE_EXTENSIONS, true)) { continue; }
            if (strpos($e['stem'], $prefix) === 0) {
                $suffix = substr($e['stem'], strlen($prefix));
                if (ctype_digit($suffix)) { $matches[(int)$suffix] = $e; }
            }
        }
        ksort($matches);
        return array_values($matches);
    }

    /** Read a named file from the ZIP, MIME-validate it, store under $dir. Returns filename or null. */
    private static function storeZipImage(\ZipArchive $zip, array $zipEntries, string $requestedName, string $dir, $rowNo, array &$d): ?string
    {
        $key = BulkImportHelper::fileMatchKey($requestedName);
        $entryName = null;
        foreach ($zipEntries as $e) {
            if ($e['base'] === $key) { $entryName = $e['name']; break; }
        }
        if ($entryName === null) {
            $d['invalid_images']++;
            Log::warning("Bulk import: ZIP image '{$requestedName}' not found (row {$rowNo}).");
            return null;
        }
        $bytes = $zip->getFromName($entryName);
        $ext = BulkImportHelper::validateImageBytes($bytes ?: null);
        if ($ext === null) {
            $d['invalid_images']++;
            Log::warning("Bulk import: ZIP image '{$requestedName}' rejected (unsafe/unsupported) (row {$rowNo}).");
            return null;
        }
        $filename = \Carbon\Carbon::now()->toDateString() . '-' . uniqid('', true) . '.' . $ext;
        Storage::disk('public')->put($dir . $filename, $bytes);
        $d['images_from_zip']++;
        return $filename;
    }

    /** Download an image from $url, MIME-validate it, store under $dir. Returns filename or null. */
    private static function storeUrlImage(string $url, string $dir, $rowNo, array &$d): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $d['failed_downloads']++;
            return null;
        }
        try {
            // Send a browser-like User-Agent + same-origin Referer; many image CDNs reject bare
            // server requests / hotlinks. (Won't defeat strong bot protection like Akamai.)
            $parts = parse_url($url);
            $referer = isset($parts['scheme'], $parts['host']) ? $parts['scheme'] . '://' . $parts['host'] . '/' : '';
            $ctx = stream_context_create(['http' => [
                'method'        => 'GET',
                'follow_location' => 1,
                'timeout'       => 25,
                'header'        => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36\r\n"
                    . "Accept: image/avif,image/webp,image/png,image/*,*/*;q=0.8\r\n"
                    . ($referer !== '' ? "Referer: {$referer}\r\n" : ''),
            ], 'https' => [
                'method'        => 'GET',
                'follow_location' => 1,
                'timeout'       => 25,
                'header'        => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36\r\n"
                    . "Accept: image/avif,image/webp,image/png,image/*,*/*;q=0.8\r\n"
                    . ($referer !== '' ? "Referer: {$referer}\r\n" : ''),
            ]]);
            $bytes = @file_get_contents($url, false, $ctx);
        } catch (\Throwable $e) {
            $bytes = false;
        }
        if ($bytes === false || $bytes === '') {
            $d['failed_downloads']++;
            Log::warning("Bulk import: image download failed for '{$url}' (row {$rowNo}).");
            return null;
        }
        $ext = BulkImportHelper::validateImageBytes($bytes);
        if ($ext === null) {
            $d['invalid_images']++;
            Log::warning("Bulk import: downloaded image '{$url}' rejected (unsafe/unsupported) (row {$rowNo}).");
            return null;
        }
        $filename = \Carbon\Carbon::now()->toDateString() . '-' . uniqid('', true) . '.' . $ext;
        Storage::disk('public')->put($dir . $filename, $bytes);
        $d['images_downloaded']++;
        return $filename;
    }
}
