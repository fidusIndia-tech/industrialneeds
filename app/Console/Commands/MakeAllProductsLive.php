<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reverses the temporary "hide expensive products" filter (see
 * App\Console\Commands\HideExpensiveProducts) by setting products.status = 1
 * on every non-deleted product that is currently not live.
 *
 * Live status is controlled solely by products.status (1 = live, 0 = not live);
 * this command touches ONLY that column (plus updated_at). It never alters name,
 * price, stock, category, brand, description, images or SEO fields.
 *
 * Dry run by default. The real update runs only with --confirm.
 */
class MakeAllProductsLive extends Command
{
    protected $signature = 'products:make-all-live
        {--dry-run : Show the affected products/count without changing anything (this is also the default).}
        {--confirm : Actually apply the update. Without this flag the command is a dry run.}';

    protected $description = 'Make every valid non-deleted product live again by setting status=1. Dry-run by default; real update needs --confirm. Updated IDs are logged.';

    private const TABLE = 'products';
    private const STATUS_COLUMN = 'status';
    private const LIVE = 1;
    private const LOG_RELATIVE = 'logs/products_make_all_live.log';

    public function handle()
    {
        if (!Schema::hasTable(self::TABLE)) {
            $this->error('Table `' . self::TABLE . '` does not exist.');
            return 1;
        }
        if (!Schema::hasColumn(self::TABLE, self::STATUS_COLUMN)) {
            $this->error('Column `' . self::STATUS_COLUMN . '` does not exist on `' . self::TABLE . '`.');
            return 1;
        }

        // Safety: never resurrect soft-deleted rows. This project's products table
        // has no deleted_at today, but guard in case it is added later.
        $hasSoftDeletes = Schema::hasColumn(self::TABLE, 'deleted_at');

        $baseQuery = function () use ($hasSoftDeletes) {
            $q = DB::table(self::TABLE);
            if ($hasSoftDeletes) {
                $q->whereNull('deleted_at');
            }
            return $q;
        };

        $totalBefore   = $baseQuery()->count();
        $liveBefore    = (clone $baseQuery())->where(self::STATUS_COLUMN, self::LIVE)->count();
        $notLiveBefore = (clone $baseQuery())->where(self::STATUS_COLUMN, '!=', self::LIVE)->count();

        $this->info('Visibility column: ' . self::TABLE . '.' . self::STATUS_COLUMN . ' (1 = live, 0 = not live)');
        if ($hasSoftDeletes) {
            $this->info('Soft-deletes detected: rows with deleted_at set are excluded.');
        }
        $this->line('');
        $this->info("BEFORE  -> total: {$totalBefore} | live: {$liveBefore} | not live: {$notLiveBefore}");

        if ($notLiveBefore === 0) {
            $this->info('All non-deleted products are already live. Nothing to do.');
            return 0;
        }

        // Breakdown so the operator can see exactly what will change.
        $byAddedBy = (clone $baseQuery())
            ->where(self::STATUS_COLUMN, '!=', self::LIVE)
            ->select('added_by', DB::raw('count(*) as c'))
            ->groupBy('added_by')->get();
        $this->line('Not-live breakdown by added_by:');
        foreach ($byAddedBy as $row) {
            $this->line(sprintf('  %-8s : %d', $row->added_by ?? '(null)', $row->c));
        }

        $affectedIds = (clone $baseQuery())
            ->where(self::STATUS_COLUMN, '!=', self::LIVE)
            ->orderBy('id')
            ->pluck('id')->all();

        $isConfirmed = (bool) $this->option('confirm');

        if (!$isConfirmed) {
            $this->line('');
            $this->warn('DRY RUN: no changes were made.');
            $this->line("Would set {$this->statusLabel()} on {$notLiveBefore} product(s).");
            $this->line('Sample of products that would be made live (up to 10):');

            $sample = (clone $baseQuery())
                ->where(self::STATUS_COLUMN, '!=', self::LIVE)
                ->select('id', 'name', 'product_code', self::STATUS_COLUMN)
                ->orderBy('id')->limit(10)->get();
            foreach ($sample as $row) {
                $this->line(sprintf(
                    '  #%-6d  code:%-14s  %s',
                    $row->id,
                    $row->product_code ?? '-',
                    mb_strimwidth((string) $row->name, 0, 55, '…')
                ));
            }
            $this->line('');
            $this->warn('Re-run with --confirm to apply the update.');
            return 0;
        }

        if (!$this->confirm("Set status=1 on {$notLiveBefore} not-live product(s)?", true)) {
            $this->info('Aborted. No changes were made.');
            return 0;
        }

        $updated = DB::transaction(function () use ($affectedIds) {
            return DB::table(self::TABLE)
                ->whereIn('id', $affectedIds)
                ->where(self::STATUS_COLUMN, '!=', self::LIVE)
                ->update([
                    self::STATUS_COLUMN => self::LIVE,
                    'updated_at'        => Carbon::now(),
                ]);
        });

        $liveAfter    = (clone $baseQuery())->where(self::STATUS_COLUMN, self::LIVE)->count();
        $notLiveAfter = (clone $baseQuery())->where(self::STATUS_COLUMN, '!=', self::LIVE)->count();

        $this->writeLog($affectedIds, $updated, $totalBefore, $liveBefore, $liveAfter);

        $this->line('');
        $this->info("Updated {$updated} product(s). status is now 1 for those rows.");
        $this->info("AFTER   -> total: {$totalBefore} | live: {$liveAfter} | not live: {$notLiveAfter}");
        $this->info('Affected IDs logged to: storage/' . self::LOG_RELATIVE);

        // Refresh caches so the storefront reflects the change immediately.
        $this->line('');
        $this->info('Clearing caches...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        $this->info('Caches cleared (cache, config, view).');

        return 0;
    }

    private function statusLabel(): string
    {
        return self::STATUS_COLUMN . '=' . self::LIVE;
    }

    private function writeLog(array $ids, int $updated, int $totalBefore, int $liveBefore, int $liveAfter): void
    {
        $path = storage_path(self::LOG_RELATIVE);
        $entry = '[' . Carbon::now()->toIso8601String() . '] '
            . "products:make-all-live --confirm | updated={$updated} "
            . "| total={$totalBefore} | live_before={$liveBefore} | live_after={$liveAfter}" . PHP_EOL
            . 'affected_ids=' . json_encode(array_values($ids)) . PHP_EOL
            . str_repeat('-', 60) . PHP_EOL;

        file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
    }
}
