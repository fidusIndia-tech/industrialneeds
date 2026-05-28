<?php

namespace App\Console\Commands;

use App\CPU\BackEndHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HideExpensiveProducts extends Command
{
    protected $signature = 'products:hide-expensive-products
        {--threshold=50000 : Selling price threshold in the system default currency (INR). Products strictly above this become inactive.}
        {--execute : Actually apply the update. Without this flag the command runs as a dry run.}';

    protected $description = '[DISABLED] Previously soft-hid live products priced above an INR threshold by setting status=0. Retired after products:make-all-live re-published everything; left in place for reference only.';

    public function handle()
    {
        // This command is intentionally disabled. It was a one-off filter that set
        // status=0 on products priced above ~INR 50000. All affected products have
        // since been re-published (see products:make-all-live and
        // storage/logs/products_make_all_live.log). Running it again would re-hide
        // them, so it is now a hard no-op.
        $this->error('products:hide-expensive-products is DISABLED and will not run.');
        $this->line('If you genuinely need to re-hide products, restore this command from version control first.');
        return 1;

        // ----- original implementation kept below for reference (unreachable) -----
        $table = 'products';
        $priceColumn = 'unit_price';
        $statusColumn = 'status';

        if (!Schema::hasTable($table)) {
            $this->error("Table `{$table}` does not exist.");
            return 1;
        }

        foreach ([$priceColumn, $statusColumn] as $col) {
            if (!Schema::hasColumn($table, $col)) {
                $this->error("Column `{$col}` does not exist on `{$table}`.");
                return 1;
            }
        }

        $thresholdInr = (float) $this->option('threshold');
        if ($thresholdInr <= 0) {
            $this->error('Threshold must be a positive number.');
            return 1;
        }

        // unit_price is stored in USD (see Admin\ProductController where it is
        // saved via BackEndHelper::currency_to_usd). Convert the INR threshold
        // to USD before querying so we compare apples to apples.
        $thresholdUsd = BackEndHelper::currency_to_usd($thresholdInr);

        $this->info("Visibility column detected: {$table}.{$statusColumn} (1 = live, 0 = hidden — used by Product::scopeActive)");
        $this->info("Price column detected:      {$table}.{$priceColumn} (stored in USD)");
        $this->info("Threshold (INR display):    ₹" . number_format($thresholdInr, 2));
        $this->info("Threshold (USD storage):    \$" . number_format($thresholdUsd, 4));

        $totalAbove = DB::table($table)->where($priceColumn, '>', $thresholdUsd)->count();
        $this->info("Total products with selling price > ₹" . number_format($thresholdInr, 2) . ": {$totalAbove}");

        $affectedQuery = DB::table($table)
            ->where($priceColumn, '>', $thresholdUsd)
            ->where($statusColumn, 1);

        $affectedCount = (clone $affectedQuery)->count();
        $alreadyHidden = $totalAbove - $affectedCount;

        $this->info("Already inactive (status=0), will be untouched: {$alreadyHidden}");
        $this->info("Currently live (status=1), will be hidden:      {$affectedCount}");

        if ($affectedCount === 0) {
            $this->info('Nothing to update.');
            return 0;
        }

        $affectedIds = (clone $affectedQuery)->pluck('id')->all();

        if (!$this->option('execute')) {
            $this->warn('Dry run: no changes were made.');
            $this->line('Sample of products that would be hidden (up to 10):');
            $sample = (clone $affectedQuery)
                ->select('id', 'name', $priceColumn)
                ->orderByDesc($priceColumn)
                ->limit(10)
                ->get();
            foreach ($sample as $row) {
                $inr = BackEndHelper::usd_to_currency($row->{$priceColumn});
                $this->line(sprintf(
                    '  #%-6d  ₹%-12s  %s',
                    $row->id,
                    number_format($inr, 2),
                    mb_strimwidth((string) $row->name, 0, 60, '…')
                ));
            }
            $this->warn('Re-run with --execute to apply the update.');
            return 0;
        }

        if (!$this->confirm("Set {$statusColumn}=0 on {$affectedCount} live product(s) priced above ₹" . number_format($thresholdInr, 2) . "?", true)) {
            $this->info('Aborted. No changes were made.');
            return 0;
        }

        $logPath = 'product-hide-logs/hidden-' . Carbon::now()->format('Y-m-d_His') . '.json';
        Storage::disk('local')->put($logPath, json_encode([
            'timestamp'      => Carbon::now()->toIso8601String(),
            'threshold_inr'  => $thresholdInr,
            'threshold_usd'  => $thresholdUsd,
            'affected_count' => $affectedCount,
            'affected_ids'   => $affectedIds,
        ], JSON_PRETTY_PRINT));

        $updated = DB::transaction(function () use ($table, $statusColumn, $affectedIds) {
            return DB::table($table)
                ->whereIn('id', $affectedIds)
                ->where($statusColumn, 1)
                ->update([
                    $statusColumn => 0,
                    'updated_at'  => Carbon::now(),
                ]);
        });

        $this->info("Updated {$updated} product(s). status is now 0 for those rows.");
        $this->info("Affected IDs logged to: storage/app/{$logPath}");
        $this->line('To reverse: re-set status=1 on the IDs in that file (DB::table(\'products\')->whereIn(\'id\', $ids)->update([\'status\' => 1])).');

        return 0;
    }
}
