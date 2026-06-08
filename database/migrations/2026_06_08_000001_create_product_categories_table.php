<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexed mirror of products.category_ids (the JSON column).
 *
 * The JSON column stays the source of truth (every write path and the admin/seller
 * edit forms keep using it). This pivot is a DERIVED read index kept in sync by a
 * Product model event + the `catalog:sync-product-categories` command, so category
 * browse can run as an indexed SQL query instead of loading the whole catalog into
 * PHP. No FK constraints on purpose: the legacy JSON has no referential integrity
 * (some ids point at deleted categories) and a FK would block the backfill.
 */
class CreateProductCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            // 1 = top-level, 2 = sub, 3 = sub-sub (mirrors the JSON "position").
            $table->unsignedTinyInteger('position')->default(0);

            // "products in category X" (any level) and "...at level N" both use this.
            $table->index(['category_id', 'position'], 'product_categories_cat_pos_idx');
            $table->index('product_id', 'product_categories_product_idx');
            // One row per (product, category); dedupes the JSON.
            $table->unique(['product_id', 'category_id'], 'product_categories_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_categories');
    }
}
