<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * One-shot Meilisearch setup/refresh for products: applies the configured index
 * settings (searchable/filterable/sortable attributes) then (re)imports every
 * searchable product. Run after provisioning Meilisearch and after large bulk
 * imports (which insert raw via DB::table and bypass Scout's model observers):
 *
 *   php artisan search:reindex-products
 *
 * No-op-safe: if SCOUT_DRIVER isn't "meilisearch" it just warns and exits.
 */
class ReindexProducts extends Command
{
    protected $signature = 'search:reindex-products';

    protected $description = 'Sync Meilisearch index settings and (re)import all products';

    public function handle(): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->warn('SCOUT_DRIVER is "' . config('scout.driver') . '", not "meilisearch" — nothing to do. '
                . 'Set SCOUT_DRIVER=meilisearch (with MEILISEARCH_HOST running) first.');
            return self::SUCCESS;
        }

        $this->info('Syncing Meilisearch index settings...');
        $this->call('scout:sync-index-settings');

        $this->info('Importing products into Meilisearch...');
        $this->call('scout:import', ['model' => \App\Model\Product::class]);

        $this->info('Done.');
        return self::SUCCESS;
    }
}
