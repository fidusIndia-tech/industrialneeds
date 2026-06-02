<?php

namespace App\Console\Commands;

use App\CPU\BackEndHelper;
use App\CPU\BulkImportHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * Safe PRICE-ONLY update from an Excel/CSV. Matches existing products by product_code (SKU/MPN/Part#)
 * and updates ONLY price columns — unit_price, purchase_price, discount, discount_type. It never
 * touches name, brand, category, details, images, image_status, stock, slug or live status, never
 * creates products, and never touches images.
 *
 * Dry-run by default (preview old→new). --execute writes a reversible backup, then updates.
 * Prices are run through the same currency_to_usd conversion the importer uses, so values stay
 * consistent with how the catalogue was originally priced.
 */
class UpdateProductPrices extends Command
{
    protected $signature = 'products:update-prices
        {file : Path to the updated Excel/CSV}
        {--execute : Apply the changes (otherwise dry-run preview)}
        {--exchange-rate= : If rows carry supplier_price instead of final prices, multiply by this}
        {--landed-cost-percent= : Added to supplier price when computing purchase_price}
        {--margin-percent= : Added to purchase price when computing unit_price}
        {--rounding=whole : whole | none | 5 | 10}
        {--allow-below : Allow unit_price below purchase_price (otherwise such rows are skipped)}
        {--limit=0 : Only process the first N rows}
        {--restore= : Restore prices from a backup JSON file produced by a previous --execute run}';

    protected $description = 'Update ONLY product prices (unit/purchase/discount) from an Excel/CSV, matched by product_code. Dry-run by default.';

    public function handle()
    {
        if ($restore = $this->option('restore')) {
            return $this->restore($restore);
        }

        $file = (string) $this->argument('file');
        if (!is_file($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }
        try {
            $rows = (new FastExcel)->import($file);
        } catch (\Throwable $e) {
            $this->error('Could not read the file: ' . $e->getMessage());
            return 1;
        }

        $apply = (bool) $this->option('execute');
        $limit = (int) $this->option('limit');
        $defaults = [
            'exchange_rate'       => $this->option('exchange-rate'),
            'landed_cost_percent' => $this->option('landed-cost-percent'),
            'margin_percent'      => $this->option('margin-percent'),
            'rounding'            => $this->option('rounding') ?: 'whole',
            'allow_below'         => (bool) $this->option('allow-below'),
        ];

        $res = \App\CPU\PriceUpdater::analyze($rows, $defaults, $limit);

        // sample preview table
        $samples = [];
        foreach (array_slice($res['changes'], 0, 30) as $c) {
            $samples[] = [
                $c['product_code'],
                isset($c['new']['unit_price']) ? round($c['old']['unit_price'], 2) . ' → ' . round($c['new']['unit_price'], 2) : '—',
                isset($c['new']['purchase_price']) ? round($c['old']['purchase_price'], 2) . ' → ' . round($c['new']['purchase_price'], 2) : '—',
            ];
        }
        if ($samples) {
            $this->info('Sample changes (old → new, stored units):');
            $this->table(['product_code', 'unit_price', 'purchase_price'], $samples);
        }
        $nfUnique = array_values(array_unique($res['not_found']));
        $this->info("Rows: {$res['processed']}   Matched: {$res['matched']}   Changed: {$res['changed']}   Not found: " . count($nfUnique) . "   Skipped: {$res['skipped']}   Invalid price: {$res['invalid']}");

        if ($nfUnique) {
            Storage::disk('local')->makeDirectory('reports');
            $nf = 'reports/price_update_not_found_' . now()->format('Ymd_His') . '.csv';
            $fh = fopen(Storage::disk('local')->path($nf), 'w'); fputcsv($fh, ['product_code']);
            foreach ($nfUnique as $c) { fputcsv($fh, [$c]); }
            fclose($fh);
            $this->warn(count($nfUnique) . ' not-found product(s) → ' . Storage::disk('local')->path($nf));
        }
        if ($res['changes']) {
            Storage::disk('local')->makeDirectory('reports');
            $cf = 'reports/price_update_changes_' . now()->format('Ymd_His') . '.csv';
            $fh = fopen(Storage::disk('local')->path($cf), 'w'); fputcsv($fh, ['product_code', 'old_unit_price', 'new_unit_price', 'old_purchase_price', 'new_purchase_price']);
            foreach ($res['changes'] as $c) { fputcsv($fh, [$c['product_code'], $c['old']['unit_price'], $c['new']['unit_price'] ?? $c['old']['unit_price'], $c['old']['purchase_price'], $c['new']['purchase_price'] ?? $c['old']['purchase_price']]); }
            fclose($fh);
            $this->line('Full change list → ' . Storage::disk('local')->path($cf));
        }

        if (!$apply) {
            $this->warn('Dry run — no prices changed. Re-run with --execute to apply.');
            return 0;
        }

        $backup = \App\CPU\PriceUpdater::applyChanges($res['changes']);
        Storage::disk('local')->makeDirectory('backups');
        $bf = 'backups/price_update_backup_' . now()->format('Ymd_His') . '.json';
        Storage::disk('local')->put($bf, json_encode($backup, JSON_PRETTY_PRINT));
        $this->info('Updated ' . count($backup) . ' product price(s). Backup: ' . Storage::disk('local')->path($bf));
        $this->line('Restore with:  php artisan products:update-prices --restore="' . Storage::disk('local')->path($bf) . '"');
        return 0;
    }

    private function restore(string $path): int
    {
        if (!is_file($path)) { $this->error("Backup not found: {$path}"); return 1; }
        $rows = json_decode((string) file_get_contents($path), true);
        if (!is_array($rows) || empty($rows)) { $this->error('Backup empty/invalid.'); return 1; }
        if (!$this->confirm('Restore prices for ' . count($rows) . ' product(s)?', true)) { $this->info('Aborted.'); return 0; }
        $n = 0;
        foreach (array_chunk($rows, 500) as $batch) {
            DB::transaction(function () use ($batch, &$n) {
                foreach ($batch as $r) {
                    if (!isset($r['id'])) continue;
                    DB::table('products')->where('id', $r['id'])->update([
                        'unit_price' => $r['unit_price'], 'purchase_price' => $r['purchase_price'],
                        'discount' => $r['discount'], 'discount_type' => $r['discount_type'], 'updated_at' => now(),
                    ]);
                    $n++;
                }
            });
        }
        $this->info("Restored {$n} product(s).");
        return 0;
    }
}
