<?php

namespace App\CPU;

use App\Model\PriceUpdateJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Shared price-only update logic used by BOTH the CLI command (products:update-prices) and the admin
 * screen. Matches existing products by product_code and only ever computes/applies the price columns
 * (unit_price, purchase_price, discount, discount_type) — never anything else, never inserts.
 *
 * The admin screen runs the heavy work in chunks behind a progress page (see processChunk + the
 * PriceUpdateJob model); the CLI uses the simpler synchronous analyze()/applyChanges() pair.
 */
class PriceUpdater
{
    /** Columns this updater is allowed to touch — nothing else, ever. */
    const PRICE_COLUMNS = ['unit_price', 'purchase_price', 'discount', 'discount_type'];

    /** Canonical fields kept per row in the temp NDJSON (everything pricing needs to recompute). */
    const PRICE_INPUT_KEYS = [
        'product_code', 'unit_price', 'purchase_price', 'supplier_price', 'discount', 'discount_type',
        'supplier_currency', 'exchange_rate', 'landed_cost_percent', 'margin_percent',
    ];

    /**
     * Extra product-code headers recognised by the PRICE UPDATE only (kept out of the shared
     * bulk importer on purpose). Normalised to lowercase + single spaces.
     */
    const MPN_HEADERS = [
        'mpn', 'manufacturer part number', 'manufacturer_part_number', 'manufacturer part no',
        'manufacturer part #', 'manufacturer part', 'mfr part number', 'mfg part number', 'mfr_part_number',
    ];

    /**
     * Map one raw sheet row to canonical fields, adding the price-update-only header support:
     *  - MPN / Manufacturer Part Number recognised as product_code
     *  - a bare `price` column treated as supplier cost (same handling as "Price [EUR]")
     * Does NOT modify the shared BulkImportHelper aliases.
     */
    public static function resolveRow(array $raw): array
    {
        $r = BulkImportHelper::mapRow($raw);

        if (trim((string) ($r['product_code'] ?? '')) === '') {
            $code = self::firstByHeaders($raw, self::MPN_HEADERS);
            if ($code !== null && trim($code) !== '') {
                $r['product_code'] = trim($code);
            }
        }

        $hasAnyPrice = is_numeric($r['unit_price'] ?? null)
            || is_numeric($r['purchase_price'] ?? null)
            || is_numeric($r['supplier_price'] ?? null);
        if (!$hasAnyPrice) {
            $bare = self::firstByHeaders($raw, ['price']);
            if ($bare !== null && is_numeric($bare)) {
                $r['supplier_price'] = $bare; // bare `price` = supplier cost (chosen mapping)
            }
        }

        return $r;
    }

    /** Pick the value of the first matching header (case/space-insensitive) from a raw row. */
    private static function firstByHeaders(array $raw, array $headers): ?string
    {
        $want = array_flip($headers);
        foreach ($raw as $k => $v) {
            $key = preg_replace('/\s+/', ' ', trim(mb_strtolower((string) $k)));
            if (isset($want[$key])) {
                $val = is_string($v) ? trim($v) : $v;
                if ($val !== null && $val !== '') {
                    return (string) $val;
                }
            }
        }
        return null;
    }

    /** Keep only the pricing-relevant fields (for the compact temp NDJSON). */
    public static function canonicalRow(array $r): array
    {
        $out = [];
        foreach (self::PRICE_INPUT_KEYS as $k) {
            if (array_key_exists($k, $r) && $r[$k] !== null && $r[$k] !== '') {
                $out[$k] = $r[$k];
            }
        }
        return $out;
    }

    /**
     * Build the price-only update fields for a resolved row.
     * @return array{_status:'ok'|'no_price'|'invalid', new?:array}
     */
    private static function buildNew(array $r, array $defaults): array
    {
        $hasSupplier = is_numeric($r['supplier_price'] ?? null);
        $hasUnit     = is_numeric($r['unit_price'] ?? null) || $hasSupplier;
        $hasPurchase = is_numeric($r['purchase_price'] ?? null) || $hasSupplier;
        $hasDiscount = isset($r['discount']) && $r['discount'] !== '' && is_numeric($r['discount']);
        if (!$hasUnit && !$hasPurchase && !$hasDiscount) {
            return ['_status' => 'no_price'];
        }

        $price = BulkImportHelper::computePricing($r, $defaults);
        if (isset($price['error'])) {
            return ['_status' => 'invalid']; // e.g. selling < purchase (unless allow_below)
        }

        $new = [];
        if ($hasUnit)     { $new['unit_price'] = BackEndHelper::currency_to_usd($price['unit_price']); }
        if ($hasPurchase) { $new['purchase_price'] = BackEndHelper::currency_to_usd($price['purchase_price']); }
        if ($hasDiscount) {
            $new['discount'] = $price['discount'];
            if (!empty($r['discount_type'])) { $new['discount_type'] = $price['discount_type']; }
        }
        return ['_status' => 'ok', 'new' => $new];
    }

    /** True if any new price column actually differs from the product's current value. */
    private static function differs(array $new, $product): bool
    {
        foreach ($new as $k => $v) {
            if ($k === 'discount_type') {
                if ((string) $product->$k !== (string) $v) { return true; }
            } elseif (round((float) $product->$k, 4) !== round((float) $v, 4)) {
                return true;
            }
        }
        return false;
    }

    // ─────────────────────────── Admin UI: fast sample + chunked apply ───────────────────────────

    /**
     * Compute the small preview sample for the admin screen. Takes already-resolved canonical rows
     * (optionally carrying '_row'), does ONE batched lookup, and returns display rows with a status
     * of will_update | not_found | skipped. Read-only.
     */
    public static function sampleRows(array $rows, array $defaults): array
    {
        $codes = [];
        foreach ($rows as $r) {
            $c = trim((string) ($r['product_code'] ?? ''));
            if ($c !== '') { $codes[$c] = true; }
        }

        $byCode = [];
        if ($codes) {
            DB::table('products')->whereIn('product_code', array_keys($codes))
                ->get(['id', 'product_code', 'name', 'unit_price', 'purchase_price'])
                ->each(function ($p) use (&$byCode) { $byCode[$p->product_code] = $p; });
        }

        $out = [];
        foreach ($rows as $r) {
            $code = trim((string) ($r['product_code'] ?? ''));
            $prod = $code !== '' ? ($byCode[$code] ?? null) : null;

            $disp = [
                'row'          => $r['_row'] ?? null,
                'product_code' => $code !== '' ? $code : '—',
                'name'         => $prod->name ?? '—',
                'old_purchase' => $prod ? (float) $prod->purchase_price : null,
                'new_purchase' => null,
                'old_unit'     => $prod ? (float) $prod->unit_price : null,
                'new_unit'     => null,
                'status'       => 'skipped',
            ];

            if (!$prod) {
                $disp['status'] = $code === '' ? 'skipped' : 'not_found';
            } else {
                $built = self::buildNew($r, $defaults);
                if (($built['_status'] ?? '') !== 'ok') {
                    $disp['status'] = 'skipped';
                } else {
                    $new = $built['new'];
                    $disp['new_purchase'] = $new['purchase_price'] ?? (float) $prod->purchase_price;
                    $disp['new_unit']     = $new['unit_price'] ?? (float) $prod->unit_price;
                    $disp['status']       = self::differs($new, $prod) ? 'will_update' : 'skipped';
                }
            }
            $out[] = $disp;
        }
        return $out;
    }

    /**
     * Apply the next chunk of a background price-update job. Reads chunkSize rows from the temp
     * NDJSON, does ONE batched lookup, applies price-only updates in a transaction, appends the
     * previous values to the backup NDJSON and unmatched codes to the not-found NDJSON, and bumps
     * the job counters. Safe to re-run (position is tracked by processed_rows).
     */
    public static function processChunk(PriceUpdateJob $job, int $chunkSize = 500): void
    {
        $path = Storage::disk('local')->path($job->file_path);
        if (!is_file($path)) {
            throw new \RuntimeException('Price update data file missing.');
        }
        $fh = fopen($path, 'r');
        if (!$fh) {
            throw new \RuntimeException('Could not open price update data file.');
        }

        // Skip already-processed lines, then read this chunk.
        $skip = (int) $job->processed_rows;
        for ($i = 0; $i < $skip; $i++) {
            if (fgets($fh) === false) { break; }
        }
        $lines = [];
        while (count($lines) < $chunkSize && ($line = fgets($fh)) !== false) {
            $lines[] = trim($line);
        }
        fclose($fh);

        $defaults = $job->import_options ?? [];

        // Decode + collect codes for one batched lookup.
        $rows = [];
        $codes = [];
        foreach ($lines as $line) {
            if ($line === '') { $rows[] = null; continue; }
            $r = json_decode($line, true);
            $rows[] = is_array($r) ? $r : null;
            if (is_array($r)) {
                $c = trim((string) ($r['product_code'] ?? ''));
                if ($c !== '') { $codes[$c] = true; }
            }
        }
        $byCode = [];
        if ($codes) {
            DB::table('products')->whereIn('product_code', array_keys($codes))
                ->get(['id', 'product_code', 'unit_price', 'purchase_price', 'discount', 'discount_type'])
                ->each(function ($p) use (&$byCode) { $byCode[$p->product_code] = $p; });
        }

        $updated = 0; $skipped = 0; $notFound = 0; $failed = 0;
        $backupLines = [];
        $notFoundLines = [];

        DB::transaction(function () use ($rows, $defaults, $byCode, &$updated, &$skipped, &$notFound, &$backupLines, &$notFoundLines) {
            foreach ($rows as $r) {
                if ($r === null) { $skipped++; continue; }
                $code = trim((string) ($r['product_code'] ?? ''));
                if ($code === '') { $skipped++; continue; }

                $prod = $byCode[$code] ?? null;
                if (!$prod) { $notFound++; $notFoundLines[] = $code; continue; }

                $built = self::buildNew($r, $defaults);
                if (($built['_status'] ?? '') !== 'ok') { $skipped++; continue; }

                $new = $built['new'];
                if (!self::differs($new, $prod)) { $skipped++; continue; }

                $backupLines[] = json_encode([
                    'id'             => (int) $prod->id,
                    'unit_price'     => $prod->unit_price,
                    'purchase_price' => $prod->purchase_price,
                    'discount'       => $prod->discount,
                    'discount_type'  => $prod->discount_type,
                ]);

                $update = array_intersect_key($new, array_flip(self::PRICE_COLUMNS)); // price columns only
                $update['updated_at'] = now();
                DB::table('products')->where('id', $prod->id)->update($update);
                $updated++;
            }
        });

        // Append file IO outside the DB transaction (native append — no full re-read).
        if ($backupLines && $job->backup_path) {
            self::appendLines(Storage::disk('local')->path($job->backup_path), $backupLines);
        }
        if ($notFoundLines && $job->not_found_path) {
            self::appendLines(Storage::disk('local')->path($job->not_found_path), $notFoundLines);
        }

        $job->processed_rows += count($lines);
        $job->updated_count  += $updated;
        $job->skipped_count  += $skipped;
        $job->not_found_count += $notFound;
        $job->failed_count   += $failed;
        $job->save();
    }

    private static function appendLines(string $absPath, array $lines): void
    {
        $f = fopen($absPath, 'a');
        if ($f) {
            fwrite($f, implode("\n", $lines) . "\n");
            fclose($f);
        }
    }

    // ─────────────────────────────── CLI: synchronous analyze/apply ───────────────────────────────

    /**
     * Analyse a sheet (iterable of raw rows) against the catalogue. READ-ONLY — no DB writes.
     * Used by the CLI command; the admin screen uses the chunked path above.
     *
     * @return array{
     *   processed:int, matched:int, changed:int, skipped:int, invalid:int,
     *   not_found:array, changes:array  // each change: ['id','product_code','new'=>[col=>val],'old'=>[...]]
     * }
     */
    public static function analyze(iterable $rows, array $defaults = [], int $limit = 0): array
    {
        $processed = 0; $matched = 0; $changed = 0; $skipped = 0; $invalid = 0;
        $notFound = [];
        $changes = [];

        foreach ($rows as $row) {
            if ($limit > 0 && $processed >= $limit) break;
            $processed++;

            $r = self::resolveRow((array) $row);
            $code = trim((string) ($r['product_code'] ?? ''));
            if ($code === '') { $skipped++; continue; }

            $built = self::buildNew($r, $defaults);
            if (($built['_status'] ?? '') === 'no_price') { $skipped++; continue; }
            if (($built['_status'] ?? '') === 'invalid')  { $invalid++; continue; }
            $new = $built['new'];

            $product = DB::table('products')->where('product_code', $code)->first();
            if (!$product) { $notFound[] = $code; continue; }
            $matched++;

            if (!self::differs($new, $product)) { $skipped++; continue; }

            $changed++;
            $changes[] = [
                'id'           => (int) $product->id,
                'product_code' => $code,
                'new'          => $new,
                'old'          => [
                    'unit_price'     => (float) $product->unit_price,
                    'purchase_price' => (float) $product->purchase_price,
                ],
            ];
        }

        return compact('processed', 'matched', 'changed', 'skipped', 'invalid', 'changes')
            + ['not_found' => $notFound];
    }

    /**
     * Apply a list of change records (from analyze()['changes']). Updates ONLY price columns and
     * returns the backup rows (previous price values) for reversibility.
     */
    public static function applyChanges(array $changes): array
    {
        $backup = [];
        foreach (array_chunk($changes, 500) as $batch) {
            DB::transaction(function () use ($batch, &$backup) {
                foreach ($batch as $c) {
                    $id = $c['id'] ?? null;
                    if (!$id || empty($c['new'])) { continue; }
                    $cur = DB::table('products')->where('id', $id)->first(self::PRICE_COLUMNS);
                    if (!$cur) { continue; }
                    $backup[] = ['id' => $id] + (array) $cur;

                    $update = array_intersect_key($c['new'], array_flip(self::PRICE_COLUMNS)); // price columns only
                    $update['updated_at'] = now();
                    DB::table('products')->where('id', $id)->update($update);
                }
            });
        }
        return $backup;
    }
}
