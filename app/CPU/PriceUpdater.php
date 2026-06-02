<?php

namespace App\CPU;

use Illuminate\Support\Facades\DB;

/**
 * Shared price-only update logic used by BOTH the CLI command (products:update-prices) and the admin
 * screen. Matches existing products by product_code and only ever computes/applies the price columns
 * (unit_price, purchase_price, discount, discount_type) — never anything else, never inserts.
 */
class PriceUpdater
{
    /** Columns this updater is allowed to touch — nothing else, ever. */
    const PRICE_COLUMNS = ['unit_price', 'purchase_price', 'discount', 'discount_type'];

    /**
     * Analyse a sheet (iterable of rows) against the catalogue. READ-ONLY — no DB writes.
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

            $r = BulkImportHelper::mapRow((array) $row);
            $code = trim((string) ($r['product_code'] ?? ''));
            if ($code === '') { $skipped++; continue; }

            $hasSupplier = is_numeric($r['supplier_price'] ?? null);
            $hasUnit     = is_numeric($r['unit_price'] ?? null) || $hasSupplier;
            $hasPurchase = is_numeric($r['purchase_price'] ?? null) || $hasSupplier;
            $hasDiscount = isset($r['discount']) && $r['discount'] !== '' && is_numeric($r['discount']);
            if (!$hasUnit && !$hasPurchase && !$hasDiscount) { $skipped++; continue; }

            $price = BulkImportHelper::computePricing($r, $defaults);
            if (isset($price['error'])) { $invalid++; continue; }

            $new = [];
            if ($hasUnit)     { $new['unit_price'] = BackEndHelper::currency_to_usd($price['unit_price']); }
            if ($hasPurchase) { $new['purchase_price'] = BackEndHelper::currency_to_usd($price['purchase_price']); }
            if ($hasDiscount) {
                $new['discount'] = $price['discount'];
                if (!empty($r['discount_type'])) { $new['discount_type'] = $price['discount_type']; }
            }

            $product = DB::table('products')->where('product_code', $code)->first();
            if (!$product) { $notFound[] = $code; continue; }
            $matched++;

            $diff = false;
            foreach ($new as $k => $v) {
                if ($k === 'discount_type') {
                    if ((string) $product->$k !== (string) $v) { $diff = true; }
                } elseif (round((float) $product->$k, 4) !== round((float) $v, 4)) {
                    $diff = true;
                }
            }
            if (!$diff) { $skipped++; continue; }

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

        return compact('processed', 'matched', 'changed', 'skipped', 'invalid', 'notFound', 'changes')
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
