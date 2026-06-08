<?php

namespace App\Console\Commands;

use App\Model\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the product_categories pivot from products.category_ids (the JSON column).
 *
 * Run once after deploying the pivot migration, and again after any bulk import
 * (which inserts products raw via DB::table and bypasses the model's saved event):
 *   php artisan catalog:sync-product-categories
 */
class SyncProductCategories extends Command
{
    protected $signature = 'catalog:sync-product-categories';

    protected $description = 'Rebuild the product_categories pivot from products.category_ids JSON';

    public function handle(): int
    {
        $this->info('Rebuilding product_categories from category_ids JSON...');
        DB::table('product_categories')->truncate();

        $processed = 0;
        $rowsInserted = 0;
        $buffer = [];

        $flush = function () use (&$buffer, &$rowsInserted) {
            if (empty($buffer)) {
                return;
            }
            // chunk inserts so a huge catalog doesn't build one giant query
            foreach (array_chunk($buffer, 1000) as $chunk) {
                DB::table('product_categories')->insert($chunk);
            }
            $rowsInserted += count($buffer);
            $buffer = [];
        };

        // No global scope / eager loads — we only need id + category_ids.
        Product::withoutGlobalScope('translate')
            ->select('id', 'category_ids')
            ->orderBy('id')
            ->chunkById(2000, function ($products) use (&$buffer, &$processed, $flush) {
                foreach ($products as $product) {
                    $processed++;
                    $decoded = json_decode($product->category_ids, true);
                    if (!is_array($decoded) || empty($decoded)) {
                        continue;
                    }
                    $seen = [];
                    foreach ($decoded as $c) {
                        if (!isset($c['id'])) {
                            continue;
                        }
                        $categoryId = (int) $c['id'];
                        if ($categoryId <= 0 || isset($seen[$categoryId])) {
                            continue;
                        }
                        $seen[$categoryId] = true;
                        $buffer[] = [
                            'product_id' => $product->id,
                            'category_id' => $categoryId,
                            'position' => isset($c['position']) ? (int) $c['position'] : 0,
                        ];
                    }
                }
                if (count($buffer) >= 5000) {
                    $flush();
                }
                $this->output->write('.');
            });

        $flush();

        $this->newLine();
        $this->info("Done. Processed {$processed} products, inserted {$rowsInserted} category links.");

        return self::SUCCESS;
    }
}
