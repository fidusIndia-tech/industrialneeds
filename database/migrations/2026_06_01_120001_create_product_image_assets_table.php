<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One downloaded image per product family, so many products in the same family/series can REUSE it
 * without an API call each. Keyed by family_key (see App\CPU\ProductImageFamilyService).
 */
class CreateProductImageAssetsTable extends Migration
{
    public function up()
    {
        Schema::create('product_image_assets', function (Blueprint $table) {
            $table->id();
            $table->string('family_key')->unique();       // e.g. SCHNEIDER_LC1D
            $table->string('brand')->nullable();
            $table->text('source_url')->nullable();        // original provider image URL
            $table->string('local_path');                  // stored filename on the public disk (product/thumbnail/<file>)
            $table->string('source_type', 40)->nullable(); // digikey / mouser / manual ...
            $table->unsignedSmallInteger('confidence')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_image_assets');
    }
}
