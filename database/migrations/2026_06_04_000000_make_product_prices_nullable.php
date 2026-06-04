<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow NULL on products.unit_price and products.purchase_price so that enquiry-based
 * products (sold on request, with no published price) can be stored without a price.
 *
 * A blank price must be stored as NULL — NOT 0 — so the frontend can distinguish
 * "Price on Request" from a genuine zero price. Existing rows keep their current values.
 *
 * Driver-aware: raw MODIFY on MySQL (production), Schema::change() elsewhere so the
 * SQLite in-memory test database migrates cleanly too.
 */
class MakeProductPricesNullable extends Migration
{
    public function up()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `products` MODIFY `unit_price` DOUBLE NULL DEFAULT NULL');
            DB::statement('ALTER TABLE `products` MODIFY `purchase_price` DOUBLE NULL DEFAULT NULL');
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->double('unit_price')->nullable()->change();
            $table->double('purchase_price')->nullable()->change();
        });
    }

    public function down()
    {
        // Coerce any NULLs back to 0 before restoring NOT NULL.
        DB::statement('UPDATE products SET unit_price = 0 WHERE unit_price IS NULL');
        DB::statement('UPDATE products SET purchase_price = 0 WHERE purchase_price IS NULL');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `products` MODIFY `unit_price` DOUBLE NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `products` MODIFY `purchase_price` DOUBLE NOT NULL DEFAULT 0');
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->double('unit_price')->nullable(false)->default(0)->change();
            $table->double('purchase_price')->nullable(false)->default(0)->change();
        });
    }
}
