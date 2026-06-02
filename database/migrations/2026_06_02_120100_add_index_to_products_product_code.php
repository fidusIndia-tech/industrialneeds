<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index products.product_code so the price-update (and any SKU/MPN lookup) can match in batches
 * without a full table scan. Additive and safe; added only if it isn't already present.
 */
class AddIndexToProductsProductCode extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('products', 'product_code')) {
            return;
        }
        if ($this->indexExists('products', 'products_product_code_index')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->index('product_code');
        });
    }

    public function down()
    {
        if ($this->indexExists('products', 'products_product_code_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_product_code_index');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return !empty($rows);
        } catch (\Throwable $e) {
            return false; // e.g. SQLite in tests — let the schema builder handle it
        }
    }
}
