<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageStatusToProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // 'fetched' (image attached) | 'needs_manual_review' (no trusted image found) | null
            $table->string('image_status', 40)->nullable()->after('images');
            // which provider supplied the image (mouser/digikey/nexar/manufacturer)
            $table->string('image_source', 40)->nullable()->after('image_status');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_status', 'image_source']);
        });
    }
}
