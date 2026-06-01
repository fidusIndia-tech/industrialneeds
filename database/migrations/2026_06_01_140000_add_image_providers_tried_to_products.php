<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which image providers have already been attempted for a product (comma-separated, e.g.
 * "digikey,element14"). Lets a provider-scoped retry skip products that already tried that provider,
 * so e.g. an Element14-only review sweep never re-burns DigiKey quota.
 */
class AddImageProvidersTriedToProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_providers_tried')->nullable()->after('image_source');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_providers_tried');
        });
    }
}
