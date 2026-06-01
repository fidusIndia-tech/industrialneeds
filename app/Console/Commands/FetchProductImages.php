<?php

namespace App\Console\Commands;

use App\CPU\BulkImportProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Phase-3 image collector: fetch trustworthy product photos by manufacturer part number and attach
 * them to existing products. Provider chain (each tried in order until a TRUSTED image is found):
 *
 *   1. Mouser          (needs MOUSER_API_KEY)
 *   2. DigiKey         (needs DIGIKEY_CLIENT_ID/SECRET)            — skipped if not configured
 *   3. Nexar/Octopart  (needs NEXAR_CLIENT_ID/SECRET)             — skipped if not configured
 *   4. Manufacturer    (Schneider/Telemecanique product page)     — opt-in via --manufacturer
 *
 * Trust rules (wrong image is worse than no image):
 *   - the result's manufacturer part number must EXACTLY match the product_code (normalised), and
 *   - the manufacturer must match the product's brand or a known equivalent (Telemecanique ≡ Schneider), and
 *   - the image URL must be present + downloadable + a real image (MIME-validated).
 * A provider that has the part's DETAILS but no usable image is recorded as "details_no_image" and
 * the chain continues. If no provider yields a trusted image, the product keeps its placeholder and
 * is marked image_status = 'needs_manual_review' (logged + written to a review CSV). NO Google/Bing
 * scraping is ever used for automatic assignment.
 *
 * Quota-aware: every product costs ~1 API call per tried provider. Dry-run shows the chosen URL +
 * per-provider trace without downloading. Only placeholder products are processed, so runs are
 * resumable across days. Reversible via the JSON backup (--restore).
 */
class FetchProductImages extends Command
{
    protected $signature = 'products:fetch-images
        {--execute : Download + attach images (otherwise dry-run: show URLs/trace, no download)}
        {--force : Also process products that already have a non-placeholder image}
        {--limit=0 : Only process the first N matching products (0 = all). Use a small N to preview.}
        {--brand= : Restrict to a single brand name}
        {--code= : Restrict to a single product_code (exact) — handy for targeted re-fetch}
        {--mouser : Also query Mouser (off by default — its image CDN blocks downloads, so it only adds classification, not images)}
        {--manufacturer : Also try the manufacturer product page (Schneider/Telemecanique) as a last resort (experimental)}
        {--max-api=0 : Stop after N API calls this run (0 = no cap). Protects your daily quota.}
        {--sleep=0 : Milliseconds to wait between products (0 = no sleep; retry/backoff handles any 429)}
        {--retries=3 : Attempts for transient API errors (timeouts / HTTP 429 / 5xx) with backoff}
        {--chunk=200 : Rows read per batch}
        {--restore= : Restore thumbnail/images/status from a backup JSON file from a previous --execute run}';

    protected $description = 'Fetch product images from distributor APIs by part number (Mouser→DigiKey→Nexar→manufacturer). Dry-run by default.';

    private $apiCalls = 0;
    private $digikeyToken = false;  // false = not fetched, null = unavailable, string = token
    private $nexarToken = false;
    private $retries = 3;
    private $digikeyRemaining = null; // DigiKey daily calls left, read from x-ratelimit-remaining

    public function handle()
    {
        if ($restorePath = $this->option('restore')) {
            return $this->restore($restorePath);
        }

        if ((string) config('services.mouser.key') === ''
            && (string) config('services.digikey.client_id') === ''
            && (string) config('services.nexar.client_id') === '') {
            $this->error('No provider configured. Set at least MOUSER_API_KEY in .env, then `php artisan config:clear`.');
            return 1;
        }

        set_time_limit(0);
        $apply   = (bool) $this->option('execute');
        $force   = (bool) $this->option('force');
        $limit   = (int) $this->option('limit');
        $maxApi  = (int) $this->option('max-api');
        $sleepMs = max(0, (int) $this->option('sleep'));
        $chunk   = max(50, (int) $this->option('chunk'));
        $this->retries = max(1, (int) $this->option('retries'));
        $brandFilter = trim((string) $this->option('brand'));

        $brandNames = DB::table('brands')->pluck('name', 'id')->toArray();

        $query = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->orderBy('id');
        if (!$force) {
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
        if (($codeFilter = trim((string) $this->option('code'))) !== '') {
            $query->where('product_code', $codeFilter);
        }

        // Mouser is opt-in (--mouser): its image CDN blocks server-side downloads, so it cannot
        // supply usable images and only wastes API budget. DigiKey is the primary source.
        $providers = ['digikey' => (bool) config('services.digikey.client_id'),
                      'mouser' => (bool) ($this->option('mouser') && config('services.mouser.key')),
                      'nexar' => (bool) config('services.nexar.client_id'),
                      'manufacturer' => (bool) $this->option('manufacturer')];
        $enabled = implode(', ', array_keys(array_filter($providers))) ?: 'none';

        $candidateTotal = (clone $query)->count();
        $this->info("Candidate products: {$candidateTotal}" . ($force ? ' (--force)' : ' (placeholder image only)'));
        $this->info(($apply ? 'EXECUTE' : 'DRY-RUN') . " — providers: {$enabled}"
            . ($maxApi > 0 ? ", max {$maxApi} API calls" : '') . ($limit > 0 ? ", limit {$limit} products" : ''));
        if ($candidateTotal === 0) {
            return 0;
        }

        $processed = 0; $attached = 0; $review = 0; $downloadFailed = 0; $invalid = 0;
        $samples = [];
        $backup = [];
        $reviewRows = [];
        $stop = false;
        $quotaHit = false;

        $handle = function ($rows) use (
            &$processed, &$attached, &$review, &$downloadFailed, &$invalid, &$samples, &$backup, &$reviewRows, &$stop, &$quotaHit,
            $brandNames, $apply, $limit, $maxApi, $sleepMs
        ) {
            foreach ($rows as $p) {
                if ($limit > 0 && $processed >= $limit) { $stop = true; return; }
                if ($maxApi > 0 && $this->apiCalls >= $maxApi) { $stop = true; return; }
                // Auto-stop the moment DigiKey's daily quota is exhausted (read from response headers).
                if ($this->digikeyRemaining !== null && $this->digikeyRemaining <= 0) { $quotaHit = true; $stop = true; return; }
                $processed++;

                $brand = $brandNames[$p->brand_id] ?? null;
                $code  = (string) $p->product_code;
                $trace = [];
                $chosen = null;   // ['provider','confidence','image'] once accepted
                $imgRes = null;   // resolveImages() output once a download succeeds

                // Walk the chain. A provider's image is only ACCEPTED if it is exactly-matched AND
                // (on --execute) actually downloads to a real image; otherwise fall through.
                // DigiKey first: its CDN allows downloads (Mouser's Akamai CDN blocks server hotlinks).
                foreach (['digikey', 'mouser', 'nexar', 'manufacturer'] as $prov) {
                    if ($maxApi > 0 && $this->apiCalls >= $maxApi) { break; }
                    $r = $this->callProvider($prov, $code, $brand);
                    $trace[$prov] = $r['status'];
                    if ($r['status'] !== 'image') { continue; }

                    if (!$apply) { // dry-run: trust the matched URL without downloading
                        $chosen = ['provider' => $prov, 'confidence' => $r['confidence'], 'image' => $r['image']];
                        break;
                    }
                    $stats = ['images_from_zip' => 0, 'images_downloaded' => 0, 'failed_downloads' => 0, 'invalid_images' => 0];
                    $res = BulkImportProcessor::resolveImages(
                        ['product_code' => $code, '_row' => $p->id, 'thumbnail_url' => $r['image']],
                        null, [], $stats
                    );
                    $downloadFailed += $stats['failed_downloads'];
                    $invalid        += $stats['invalid_images'];
                    if ($res['has_image']) {
                        $chosen = ['provider' => $prov, 'confidence' => $r['confidence'], 'image' => $r['image']];
                        $imgRes = $res;
                        break;
                    }
                    $trace[$prov] = 'image_undownloadable'; // could not fetch a valid image — try next provider
                }

                if ($sleepMs > 0) { usleep($sleepMs * 1000); }
                $traceStr = $this->traceString($trace);

                if (!$chosen) {
                    $review++;
                    $reviewRows[] = [$p->id, $code, $brand, $traceStr];
                    if (count($samples) < 25) { $samples[] = [$p->id, $code, 'REVIEW: ' . $traceStr]; }
                    if ($apply) {
                        $backup[] = ['id' => $p->id, 'thumbnail' => $p->thumbnail, 'images' => $p->images, 'image_status' => $p->image_status, 'image_source' => $p->image_source];
                        DB::table('products')->where('id', $p->id)->update(['image_status' => 'needs_manual_review', 'updated_at' => now()]);
                    }
                    Log::warning('fetch-images: no trusted image', ['id' => $p->id, 'code' => $code, 'brand' => $brand, 'trace' => $trace]);
                    continue;
                }

                if (count($samples) < 25) { $samples[] = [$p->id, $code, $chosen['provider'] . ' (' . $chosen['confidence'] . '): ' . $chosen['image']]; }
                if (!$apply) { continue; }

                $backup[] = ['id' => $p->id, 'thumbnail' => $p->thumbnail, 'images' => $p->images, 'image_status' => $p->image_status, 'image_source' => $p->image_source];
                DB::table('products')->where('id', $p->id)->update([
                    'thumbnail'    => $imgRes['thumbnail'],
                    'images'       => json_encode($imgRes['images']),
                    'image_status' => 'fetched',
                    'image_source' => $chosen['provider'],
                    'updated_at'   => now(),
                ]);
                $attached++;
            }
        };

        $query->chunkById($chunk, function ($rows) use ($handle, $apply, &$stop) {
            $apply ? DB::transaction(fn () => $handle($rows)) : $handle($rows);
            return $stop ? false : true;
        }, 'id');

        if ($samples) {
            $this->line('');
            $this->info('Sample results:');
            $this->table(['ID', 'product_code', 'provider / status'],
                array_map(fn ($s) => [$s[0], $s[1], mb_strimwidth((string) $s[2], 0, 85, '…')], $samples));
        }

        $this->info("Processed: {$processed}   API calls: {$this->apiCalls}   "
            . ($apply ? "Attached: {$attached}   " : "Would attach: " . ($processed - $review) . "   ")
            . "Needs review: {$review}" . ($apply ? "   Download failed: {$downloadFailed}   Invalid: {$invalid}" : ''));
        if ($this->digikeyRemaining !== null) {
            $this->line("DigiKey daily quota remaining: {$this->digikeyRemaining}");
        }
        if ($quotaHit) {
            $this->warn('Stopped early: DigiKey daily quota reached. Re-run after it resets (next day) to continue — it resumes automatically.');
        }

        // Write a review CSV (both modes) so you have a list to handle manually.
        if ($reviewRows) {
            Storage::disk('local')->makeDirectory('reports');
            $rel = 'reports/fetch_images_review_' . now()->format('Ymd_His') . '.csv';
            $abs = Storage::disk('local')->path($rel);
            $fh = fopen($abs, 'w');
            fputcsv($fh, ['product_id', 'product_code', 'brand', 'providers_tried']);
            foreach ($reviewRows as $r) { fputcsv($fh, $r); }
            fclose($fh);
            $this->warn(count($reviewRows) . " product(s) need manual review → {$abs}");
        }

        if (!$apply) {
            $this->warn('Dry run — no images downloaded, no DB changes. Add --execute to attach.');
            return 0;
        }

        Storage::disk('local')->makeDirectory('backups');
        $backupFile = 'backups/product_fetch_images_backup_' . now()->format('Ymd_His') . '.json';
        Storage::disk('local')->put($backupFile, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('Backup written to: ' . Storage::disk('local')->path($backupFile));
        $this->line('Restore with:  php artisan products:fetch-images --restore="' . Storage::disk('local')->path($backupFile) . '"');
        return 0;
    }

    // ---------------------------------------------------------------------
    // Provider chain
    // ---------------------------------------------------------------------

    private function callProvider(string $provider, string $code, ?string $brand): array
    {
        switch ($provider) {
            case 'mouser':       return $this->mouserLookup($code, $brand);
            case 'digikey':      return $this->digikeyLookup($code, $brand);
            case 'nexar':        return $this->nexarLookup($code, $brand);
            case 'manufacturer': return $this->manufacturerLookup($code, $brand);
        }
        return ['status' => 'skipped'];
    }

    /** Pick the trusted image from a list of [mpn, manufacturer, image] candidates. */
    private function pickTrusted(array $candidates, string $code, ?string $brand, string $confidence): array
    {
        $want = $this->normalize($code);
        $exactNoImage = false;
        foreach ($candidates as $c) {
            $mpn = $this->normalize($c['mpn'] ?? '');
            $img = trim((string) ($c['image'] ?? ''));
            if ($mpn !== $want) { continue; }
            if ($img === '') { $exactNoImage = true; continue; }
            if ($this->brandMatches($brand, (string) ($c['manufacturer'] ?? ''))) {
                return ['status' => 'image', 'image' => $img, 'confidence' => $confidence];
            }
            // exact MPN + image but brand mismatch → not trusted (could be a different maker's part)
            $exactNoImage = true;
        }
        return ['status' => $exactNoImage ? 'details_no_image' : 'no_match'];
    }

    private function mouserLookup(string $code, ?string $brand): array
    {
        $key = (string) config('services.mouser.key');
        if ($key === '' || !$this->option('mouser')) { return ['status' => 'skipped']; }
        $this->apiCalls++;
        try {
            $resp = Http::timeout(25)->acceptJson()->post(
                'https://api.mouser.com/api/v1/search/partnumber?apiKey=' . urlencode($key),
                ['SearchByPartRequest' => ['mouserPartNumber' => $code, 'partSearchOptions' => '']]
            );
        } catch (\Throwable $e) {
            return ['status' => 'error', 'note' => $e->getMessage()];
        }
        if (!$resp->ok()) { return ['status' => 'error', 'note' => 'HTTP ' . $resp->status()]; }
        $json = $resp->json();
        if (!empty($json['Errors'])) { return ['status' => 'error', 'note' => $json['Errors'][0]['Message'] ?? 'API error']; }
        $cands = [];
        foreach (($json['SearchResults']['Parts'] ?? []) as $part) {
            $cands[] = ['mpn' => $part['ManufacturerPartNumber'] ?? '', 'manufacturer' => $part['Manufacturer'] ?? '', 'image' => $part['ImagePath'] ?? ''];
        }
        return $this->pickTrusted($cands, $code, $brand, 'high');
    }

    private function digikeyLookup(string $code, ?string $brand): array
    {
        $id = (string) config('services.digikey.client_id');
        $secret = (string) config('services.digikey.client_secret');
        if ($id === '' || $secret === '') { return ['status' => 'skipped']; }

        // Up to two passes so an expired token can be refreshed once mid-run.
        for ($authTry = 0; $authTry < 2; $authTry++) {
            $token = $this->digikeyToken($id, $secret);
            if (!$token) { return ['status' => 'error', 'note' => 'digikey auth failed']; }

            $this->apiCalls++;
            $resp = $this->retryingPost(fn () => Http::withToken($token)
                ->withHeaders(['X-DIGIKEY-Client-Id' => $id, 'X-DIGIKEY-Locale-Site' => 'US', 'X-DIGIKEY-Locale-Currency' => 'USD'])
                ->timeout(25)->acceptJson()
                ->post('https://api.digikey.com/products/v4/search/keyword', ['Keywords' => $code, 'Limit' => 10]));

            // Track DigiKey's remaining daily quota so the run can auto-stop before hitting 429s.
            if ($resp) {
                $rem = $resp->header('x-ratelimit-remaining');
                if ($rem !== null && $rem !== '') { $this->digikeyRemaining = (int) $rem; }
            }

            if ($resp && $resp->status() === 401 && $authTry === 0) {
                $this->digikeyToken = false; // token expired/revoked — re-auth and retry once
                continue;
            }
            if (!$resp || !$resp->ok()) {
                return ['status' => 'error', 'note' => $resp ? ('HTTP ' . $resp->status()) : 'request failed'];
            }
            $cands = [];
            foreach (($resp->json('Products') ?? []) as $prod) {
                $cands[] = [
                    'mpn'          => $prod['ManufacturerProductNumber'] ?? ($prod['ManufacturerPartNumber'] ?? ''),
                    'manufacturer' => $prod['Manufacturer']['Name'] ?? ($prod['Manufacturer']['Value'] ?? ''),
                    'image'        => $prod['PhotoUrl'] ?? '',
                ];
            }
            return $this->pickTrusted($cands, $code, $brand, 'high');
        }
        return ['status' => 'error', 'note' => 'digikey 401'];
    }

    /** Cached DigiKey OAuth token (client-credentials), fetched with retry/backoff. */
    private function digikeyToken(string $id, string $secret): ?string
    {
        if ($this->digikeyToken !== false) {
            return $this->digikeyToken; // string or null (cached)
        }
        $resp = $this->retryingPost(fn () => Http::asForm()->timeout(20)->post(
            'https://api.digikey.com/v1/oauth2/token',
            ['client_id' => $id, 'client_secret' => $secret, 'grant_type' => 'client_credentials']
        ));
        $this->digikeyToken = ($resp && $resp->ok()) ? ($resp->json('access_token') ?: null) : null;
        return $this->digikeyToken;
    }

    /**
     * Run an HTTP request closure with retries + exponential backoff on transient failures
     * (network exception, HTTP 429, HTTP 5xx). Honors a numeric Retry-After header on 429.
     * Returns the final Response (possibly an error response) or null if every attempt threw.
     */
    private function retryingPost(callable $request): ?\Illuminate\Http\Client\Response
    {
        $attempts = max(1, $this->retries);
        $resp = null;
        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $resp = $request();
            } catch (\Throwable $e) {
                $resp = null; // network/timeout — retryable
            }
            if ($resp !== null) {
                if ($resp->ok()) { return $resp; }
                $status = $resp->status();
                if ($status !== 429 && $status < 500) { return $resp; } // non-retryable (e.g. 400/401/404)
            }
            if ($i < $attempts) {
                usleep($this->backoffMs($i, $resp) * 1000);
            }
        }
        return $resp;
    }

    /** Backoff delay in ms: honor Retry-After on 429, else exponential (0.8s,1.6s,3.2s…) capped at 8s + jitter. */
    private function backoffMs(int $attempt, ?\Illuminate\Http\Client\Response $resp): int
    {
        if ($resp !== null && $resp->status() === 429) {
            $ra = $resp->header('Retry-After');
            if (is_numeric($ra)) { return min(20000, max(1000, (int) $ra * 1000)); }
        }
        return min(8000, (int) (800 * (2 ** ($attempt - 1)))) + random_int(0, 300);
    }

    private function nexarLookup(string $code, ?string $brand): array
    {
        $id = (string) config('services.nexar.client_id');
        $secret = (string) config('services.nexar.client_secret');
        if ($id === '' || $secret === '') { return ['status' => 'skipped']; }

        if ($this->nexarToken === false) {
            try {
                $tok = Http::asForm()->timeout(20)->post('https://identity.nexar.com/connect/token', [
                    'client_id' => $id, 'client_secret' => $secret, 'grant_type' => 'client_credentials',
                ]);
                $this->nexarToken = $tok->ok() ? ($tok->json('access_token') ?: null) : null;
            } catch (\Throwable $e) { $this->nexarToken = null; }
        }
        if (!$this->nexarToken) { return ['status' => 'error', 'note' => 'nexar auth failed']; }

        $this->apiCalls++;
        $gql = 'query($q:String!){ supSearchMpn(q:$q, limit:5){ results{ part{ mpn manufacturer{ name } bestImage{ url } } } } }';
        try {
            $resp = Http::withToken($this->nexarToken)->timeout(25)->acceptJson()
                ->post('https://api.nexar.com/graphql', ['query' => $gql, 'variables' => ['q' => $code]]);
        } catch (\Throwable $e) {
            return ['status' => 'error', 'note' => $e->getMessage()];
        }
        if (!$resp->ok()) { return ['status' => 'error', 'note' => 'HTTP ' . $resp->status()]; }
        $cands = [];
        foreach (($resp->json('data.supSearchMpn.results') ?? []) as $res) {
            $part = $res['part'] ?? [];
            $cands[] = ['mpn' => $part['mpn'] ?? '', 'manufacturer' => $part['manufacturer']['name'] ?? '', 'image' => $part['bestImage']['url'] ?? ''];
        }
        return $this->pickTrusted($cands, $code, $brand, 'high');
    }

    /**
     * Experimental last resort: the Schneider Electric product page (Telemecanique is a Schneider
     * brand). Only used with --manufacturer. Conservative: accepts the page's og:image ONLY when the
     * exact part number appears on the page (medium confidence).
     */
    private function manufacturerLookup(string $code, ?string $brand): array
    {
        if (!$this->option('manufacturer')) { return ['status' => 'skipped']; }
        if (!$this->brandMatches($brand, 'Schneider Electric') && !$this->brandMatches($brand, 'Telemecanique')) {
            return ['status' => 'skipped'];
        }
        $this->apiCalls++;
        $url = 'https://www.se.com/ww/en/product/' . rawurlencode($code) . '/';
        try {
            $resp = Http::timeout(20)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);
        } catch (\Throwable $e) {
            return ['status' => 'error', 'note' => $e->getMessage()];
        }
        if (!$resp->ok()) { return ['status' => 'no_match']; }
        $html = (string) $resp->body();
        // Require the exact part number to appear on the page before trusting any image.
        if (stripos($html, $code) === false) { return ['status' => 'no_match']; }
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return ['status' => 'image', 'image' => html_entity_decode($m[1]), 'confidence' => 'medium'];
        }
        return ['status' => 'details_no_image'];
    }

    // ---------------------------------------------------------------------

    /** True if $mfr matches $brand or a known equivalent (Telemecanique ≡ Schneider). Unknown brand => true. */
    private function brandMatches(?string $brand, string $mfr): bool
    {
        $bn = $this->normalize((string) $brand);
        if ($bn === '') { return true; }
        $mn = $this->normalize($mfr);
        if ($mn === '') { return false; }

        $tokens = [$bn];
        $equiv = [
            'telemecanique'       => ['schneiderelectric', 'schneider', 'telemecaniquesensors'],
            'telemecaniquesensors' => ['schneiderelectric', 'schneider', 'telemecanique'],
            'schneider'           => ['telemecanique', 'schneiderelectric'],
            'schneiderelectric'   => ['telemecanique', 'schneider'],
        ];
        foreach ($equiv as $k => $vs) {
            if (strpos($bn, $k) !== false) { $tokens = array_merge($tokens, $vs); }
        }
        foreach (array_unique($tokens) as $t) {
            if ($t !== '' && (strpos($mn, $t) !== false || strpos($t, $mn) !== false)) { return true; }
        }
        return false;
    }

    private function normalize(string $v): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($v));
    }

    private function traceString(array $trace): string
    {
        $parts = [];
        foreach ($trace as $prov => $st) { $parts[] = "{$prov}={$st}"; }
        return implode(', ', $parts);
    }

    /** Restore thumbnail/images/status for every row recorded in a backup JSON file. */
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
        if (!$this->confirm('Restore ' . count($rows) . ' product(s) from this backup?', true)) {
            $this->info('Aborted.');
            return 0;
        }
        $restored = 0;
        foreach (array_chunk($rows, 500) as $batch) {
            DB::transaction(function () use ($batch, &$restored) {
                foreach ($batch as $r) {
                    if (!isset($r['id'])) continue;
                    DB::table('products')->where('id', $r['id'])->update([
                        'thumbnail'    => $r['thumbnail'] ?? 'def.png',
                        'images'       => $r['images'] ?? json_encode(['def.png']),
                        'image_status' => $r['image_status'] ?? null,
                        'image_source' => $r['image_source'] ?? null,
                        'updated_at'   => now(),
                    ]);
                    $restored++;
                }
            });
        }
        $this->info("Restored {$restored} product(s).");
        return 0;
    }
}
