<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductImportJobsTable extends Migration
{
    public function up()
    {
        Schema::create('product_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->nullable();          // temp NDJSON of cleaned rows (relative to local disk)
            $table->string('original_file_name')->nullable();
            $table->string('status')->default('pending');     // pending, processing, completed, failed, cancelled

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->unsignedInteger('new_brands_count')->default(0);
            $table->unsignedInteger('new_categories_count')->default(0);
            $table->unsignedInteger('new_sub_categories_count')->default(0);
            $table->unsignedInteger('new_sub_sub_categories_count')->default(0);

            $table->unsignedInteger('calculated_purchase_price_count')->default(0);
            $table->unsignedInteger('calculated_selling_price_count')->default(0);
            $table->unsignedInteger('default_unit_used_count')->default(0);
            $table->unsignedInteger('default_moq_used_count')->default(0);
            $table->unsignedInteger('default_stock_used_count')->default(0);

            $table->text('error_message')->nullable();
            $table->longText('failed_rows')->nullable();       // JSON [{row, reason}]
            $table->text('import_options')->nullable();        // JSON snapshot of upload-form defaults
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('locked_at')->nullable();        // lightweight guard against concurrent chunk runs

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_import_jobs');
    }
}
