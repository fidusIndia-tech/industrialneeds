<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageCountsToProductImportJobs extends Migration
{
    public function up()
    {
        Schema::table('product_import_jobs', function (Blueprint $table) {
            $table->unsignedInteger('with_images_count')->default(0)->after('default_stock_used_count');
            $table->unsignedInteger('without_images_count')->default(0)->after('with_images_count');
            $table->unsignedInteger('images_from_zip_count')->default(0)->after('without_images_count');
            $table->unsignedInteger('images_downloaded_count')->default(0)->after('images_from_zip_count');
            $table->unsignedInteger('failed_image_downloads_count')->default(0)->after('images_downloaded_count');
            $table->unsignedInteger('invalid_images_count')->default(0)->after('failed_image_downloads_count');
        });
    }

    public function down()
    {
        Schema::table('product_import_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'with_images_count', 'without_images_count', 'images_from_zip_count',
                'images_downloaded_count', 'failed_image_downloads_count', 'invalid_images_count',
            ]);
        });
    }
}
