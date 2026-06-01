<?php

namespace App\Console\Commands;

use App\CPU\BulkImportHelper;
use App\CPU\BulkImportProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * Images-only backfill for already-imported products. Two sources (use either or both):
 *   --zip : match images by product_code ("<code>.<ext>" => thumbnail, "<code>_1.<ext>" ... => gallery)
 *   --csv : a CSV mapping product_code -> thumbnail_url / gallery_urls (comma-separated) to download
 * Per product the resolver tries URL first, then ZIP auto-match. Every file is MIME-validated
 * (jpg/jpeg/png/webp); broken URLs / invalid files are counted and skipped, never fatal.
 *
 * Scope: products that have a product_code and currently use the placeholder image (def.png / empty).
 * Pass --force to also overwrite products that already have a real image.
 *
 * Safety: dry-run by default (no files written). With --execute it writes matched images to the
 * public disk, updates thumbnail/images, and writes a JSON backup (id, thumbnail, images) that can
 * be restored with --restore=<path>. (Restoring reverts the DB pointers; the written image files are
 * left on disk — harmless orphans you can clear later if desired.)
 */
class BackfillProductImages extends Command
{
    protected $signature = 'products:backfill-images
        {--zip= : Path to a ZIP of images named by product_code}
        {--csv= : Path to a CSV mapping product_code -> thumbnail_url / gallery_urls}
        {--execute : Actually apply the changes (otherwise dry-run)}
        {--force : Also overwrite products that already have a non-placeholder image}
        {--limit=0 : Only process the first N matching products (0 = all)}
        {--brand= : Restrict to a single brand name}
        {--chunk=300 : Rows read per batch}
        {--restore= : Restore thumbnail/images from a backup JSON file produced by a previous --execute run}';

    protected $description = 'Attach images to existing products by matching a ZIP on product_code. Dry-run by default.';

    public function handle()
    {
        if ($restorePath = $this->option('restore')) {
            return $this->restore($restorePath);
        }

        set_time_limit(0);
        $memLimit = (string) ini_get('memory_limit');
        if (strpos($memLimit, '-1') === false && (int) preg_replace('/[^0-9]/', '', $memLimit) < 1024) {
            @ini_set('memory_limit', '1024M');
        }

        $zipPath = (string) $this->option('zip');
        $csvPath = (string) $this->option('csv');
        if ($zipPath === '' && $csvPath === '') {
            $this->error('Provide at least one source: --zip=images.zip and/or --csv=urls.csv');
            return 1;
        }

        // Source 1: ZIP matched by product_code.
        $zip = null;
        $entries = [];
        if ($zipPath !== '') {
            if (!is_file($zipPath)) {
                $this->error("ZIP not found: {$zipPath}");
                return 1;
            }
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                $this->error("Could not open ZIP: {$zipPath}");
                return 1;
            }
            $entries = BulkImportProcessor::zipEntries($zip);
            $this->info('ZIP entries (files): ' . count($entries));
        }

        // Source 2: CSV of product_code -> thumbnail_url / gallery_urls (downloaded on --execute).
        $urlMap = [];
        if ($csvPath !== '') {
            if (!is_file($csvPath)) {
                $this->error("CSV not found: {$csvPath}");
                if ($zip) { $zip->close(); }
                return 1;
            }
            try {
                $rows = (new FastExcel)->import($csvPath);
            } catch (\Throwable $e) {
                $this->error('Could not read CSV: ' . $e->getMessage());
                if ($zip) { $zip->close(); }
                return 1;
            }
            foreach ($rows as $r) {
                $row  = BulkImportHelper::mapRow((array) $r);          // canonicalise headers (Part#, SKU, ...)
                $code = mb_strtolower(trim((string) ($row['product_code'] ?? '')));
                if ($code === '') { continue; }
                $thumb = trim((string) ($row['thumbnail_url'] ?? ''));
                $gal   = trim((string) ($row['gallery_urls'] ?? ''));
                if ($thumb === '' && $gal === '') { continue; }
                $urlMap[$code] = ['thumbnail_url' => $thumb, 'gallery_urls' => $gal];
            }
            $this->info('CSV rows with image URLs: ' . count($urlMap));
        }

        $apply  = (bool) $this->option('execute');
        $force  = (bool) $this->option('force');
        $limit  = (int) $this->option('limit');
        $chunk  = max(50, (int) $this->option('chunk'));
        $brandFilter = trim((string) $this->option('brand'));

        $brandNames = DB::table('brands')->pluck('name', 'id')->toArray();

        $query = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->orderBy('id');

        if (!$force) {
            // Only products still on the placeholder image.
            $query->where(function ($q) {
                $q->whereNull('thumbnail')->orWhere('thumbnail', '')->orWhere('thumbnail', 'def.png');
            });
        }
        if ($brandFilter !== '') {
            $brandId = array_search(mb_strtolower($brandFilter), array_map('mb_strtolower', $brandNames), true);
            if ($brandId === false) {
                $this->error("Brand '{$brandFilter}' not found.");
                return 1;
            }
            $query->where('brand_id', $brandId);
        }

        $candidateTotal = (clone $query)->count();
        $this->info("Candidate products: {$candidateTotal}" . ($force ? ' (--force)' : ' (placeholder image only)'));
        if ($candidateTotal === 0) {
            $zip->close();
            return 0;
        }

        $scanned = 0;
        $matched = 0;       // products that have a ZIP match (dry-run) / were updated (execute)
        $fromZip = 0;       // image files written
        $invalid = 0;       // invalid/unsafe files skipped
        $samples = [];
        $backup = [];

        $downloaded = 0;
        $failedDl = 0;
        $handleRows = function ($rows) use (&$scanned, &$matched, &$fromZip, &$downloaded, &$failedDl, &$invalid, &$samples, &$backup, $zip, $entries, $urlMap, $apply) {
            foreach ($rows as $p) {
                $scanned++;
                $code = (string) $p->product_code;
                $urls = $urlMap[mb_strtolower(trim($code))] ?? null;

                if (!$apply) {
                    // "Would match" = a CSV URL exists for this code, or the ZIP has a matching file.
                    if ($urls !== null || ($zip && BulkImportProcessor::zipHasImagesForCode($entries, $code))) {
                        $matched++;
                        if (count($samples) < 15) {
                            $samples[] = ['id' => $p->id, 'code' => $code, 'src' => $urls !== null ? 'URL' : 'ZIP'];
                        }
                    }
                    continue;
                }

                $stats = ['images_from_zip' => 0, 'images_downloaded' => 0, 'failed_downloads' => 0, 'invalid_images' => 0];
                $product = ['product_code' => $code, '_row' => $p->id];
                if ($urls !== null) {
                    $product['thumbnail_url'] = $urls['thumbnail_url'];
                    $product['gallery_urls']  = $urls['gallery_urls'];
                }
                $img = BulkImportProcessor::resolveImages($product, $zip, $entries, $stats);
                $fromZip    += $stats['images_from_zip'];
                $downloaded += $stats['images_downloaded'];
                $failedDl   += $stats['failed_downloads'];
                $invalid    += $stats['invalid_images'];

                if (!$img['has_image']) {
                    continue; // no match for this code — leave the placeholder
                }

                $backup[] = ['id' => $p->id, 'thumbnail' => $p->thumbnail, 'images' => $p->images];
                DB::table('products')->where('id', $p->id)->update([
                    'thumbnail'  => $img['thumbnail'],
                    'images'     => json_encode($img['images']),
                    'updated_at' => now(),
                ]);
                $matched++;
                if (count($samples) < 15) {
                    $samples[] = ['id' => $p->id, 'code' => $code, 'thumb' => $img['thumbnail']];
                }
            }
        };

        // chunkById (not chunk): --execute changes thumbnail, which is part of the WHERE filter.
        if ($limit > 0) {
            $rows = (clone $query)->limit($limit)->get();
            $apply ? DB::transaction(fn () => $handleRows($rows)) : $handleRows($rows);
        } else {
            $query->chunkById($chunk, function ($rows) use ($apply, $handleRows) {
                $apply ? DB::transaction(fn () => $handleRows($rows)) : $handleRows($rows);
            }, 'id');
        }

        if ($zip) { $zip->close(); }

        if ($samples) {
            $this->line('');
            $this->info('Sample matches:');
            $this->table(['ID', 'product_code', 'source / thumbnail'],
                array_map(fn ($s) => [$s['id'], $s['code'], $s['thumb'] ?? ($s['src'] ?? '(matched)')], $samples));
        }

        $this->info("Scanned: {$scanned}   Matched: {$matched}   From ZIP: {$fromZip}   Downloaded: {$downloaded}   Failed downloads: {$failedDl}   Invalid skipped: {$invalid}");

        if (!$apply) {
            $this->warn('Dry run — no files written, no changes made. Re-run with --execute to apply.');
            return 0;
        }

        Storage::disk('local')->makeDirectory('backups');
        $backupFile = 'backups/product_images_backup_' . now()->format('Ymd_His') . '.json';
        Storage::disk('local')->put($backupFile, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Updated {$matched} product(s).");
        $this->info('Backup written to: ' . Storage::disk('local')->path($backupFile));
        $this->line('Restore with:  php artisan products:backfill-images --restore="' . Storage::disk('local')->path($backupFile) . '"');
        return 0;
    }

    /** Restore thumbnail/images for every row recorded in a backup JSON file. */
    private function restore(string $path): int
    {
        if (!is_file($path)) {
            $this->error("Backup file not found: {$path}");
            return 1;
        }
        $rows = json_decode((string) file_get_contents($path), true);
        if (!is_array($rows) || empty($rows)) {
            $this->error('Backup file is empty or invalid.');
            return 1;
        }
        if (!$this->confirm('Restore images for ' . count($rows) . ' product(s) from this backup?', true)) {
            $this->info('Aborted.');
            return 0;
        }
        $restored = 0;
        foreach (array_chunk($rows, 500) as $batch) {
            DB::transaction(function () use ($batch, &$restored) {
                foreach ($batch as $r) {
                    if (!isset($r['id'])) continue;
                    DB::table('products')->where('id', $r['id'])->update([
                        'thumbnail'  => $r['thumbnail'] ?? 'def.png',
                        'images'     => $r['images'] ?? json_encode(['def.png']),
                        'updated_at' => now(),
                    ]);
                    $restored++;
                }
            });
        }
        $this->info("Restored {$restored} product(s).");
        return 0;
    }
}
