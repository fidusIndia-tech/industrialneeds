<?php

namespace App\Console\Commands;

use App\Jobs\FetchProductImageJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Enqueue FetchProductImageJob for products that still need an image. Jobs are pushed onto the
 * 'database' connection / 'images' queue (so they run via a worker WITHOUT changing the app's global
 * sync queue — emails etc. keep working). Run a worker to process them:
 *
 *   php artisan queue:work database --queue=images --tries=3 --stop-when-empty
 *
 * Resumable: only products on the placeholder (or queued/failed/manual_review) are selected, so it is
 * always safe to re-run. Real/manual images are never selected.
 */
class DispatchProductImageJobs extends Command
{
    protected $signature = 'products:dispatch-image-jobs
        {--limit=0 : Max products to enqueue (0 = all matching)}
        {--brand= : Restrict to one brand name}
        {--code= : Restrict to one product_code}
        {--include-failed : Also re-enqueue products in the failed state}
        {--include-review : Also re-enqueue products already marked manual_review}
        {--allow-overwrite : Allow overwriting fetched/reused/real images (use with care)}
        {--provider= : Use only these providers (comma-separated, e.g. element14). Skips others AND only picks products that have not already tried them — so an Element14-only sweep never re-burns DigiKey quota.}
        {--sync : Run each job inline now instead of queuing (handy for a quick test)}';

    protected $description = 'Queue background image fetching for products needing images (family reuse + provider lookup).';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $allowOverwrite = (bool) $this->option('allow-overwrite');
        $providers = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('provider')))));

        $query = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->orderBy('id');

        if ($allowOverwrite) {
            // everything with a product code is fair game
        } else {
            // 'placeholder' = needs dispatch (in-flight 'queued' jobs are NOT re-enqueued).
            $statuses = ['placeholder'];
            if ($this->option('include-failed')) { $statuses[] = 'failed'; }
            if ($this->option('include-review')) { $statuses[] = 'manual_review'; }
            $query->whereIn('image_status', $statuses);
        }

        // Provider-scoped retry: only pick products that have NOT already tried a requested provider
        // (so e.g. an Element14-only sweep never re-touches DigiKey).
        if (!empty($providers)) {
            $query->where(function ($q) use ($providers) {
                foreach ($providers as $pr) {
                    $q->orWhere(function ($q2) use ($pr) {
                        $q2->whereNull('image_providers_tried')
                           ->orWhere('image_providers_tried', 'not like', '%' . $pr . '%');
                    });
                }
            });
        }

        if (($brand = trim((string) $this->option('brand'))) !== '') {
            $brandId = DB::table('brands')->whereRaw('LOWER(name) = ?', [mb_strtolower($brand)])->value('id');
            if (!$brandId) {
                $this->error("Brand '{$brand}' not found.");
                return 1;
            }
            $query->where('brand_id', $brandId);
        }
        if (($code = trim((string) $this->option('code'))) !== '') {
            $query->where('product_code', $code);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $ids = $query->pluck('id');
        $this->info($ids->count() . ' product(s) to ' . ($this->option('sync') ? 'process now' : 'enqueue') . '.');
        if ($ids->isEmpty()) {
            return 0;
        }

        $only = !empty($providers) ? $providers : null;
        $bar = $this->output->createProgressBar($ids->count());
        foreach ($ids as $id) {
            if ($this->option('sync')) {
                FetchProductImageJob::dispatchSync($id, $allowOverwrite, $only);
            } else {
                FetchProductImageJob::dispatch($id, $allowOverwrite, $only)
                    ->onConnection('database')
                    ->onQueue('images');
                // mark as queued so progress/filters reflect it (only from a not-yet-done state)
                DB::table('products')->where('id', $id)
                    ->where(function ($q) {
                        $q->whereNull('image_status')->orWhereIn('image_status', ['placeholder', 'failed']);
                    })
                    ->update(['image_status' => 'queued']);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        if ($this->option('sync')) {
            $this->info('Done (ran inline).');
        } else {
            $this->info('Enqueued. Start a worker to process them:');
            $this->line('  php artisan queue:work database --queue=images --tries=3 --stop-when-empty');
        }
        return 0;
    }
}
