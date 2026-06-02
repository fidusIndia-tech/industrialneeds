<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks a chunked background price-only update so progress survives page refreshes. Rows to apply
 * live in a temp NDJSON file (file_path); chunks are processed by App\CPU\PriceUpdater::processChunk
 * and the counters here are updated after every chunk. Parallel to product_import_jobs — never
 * touches the bulk-import flow.
 */
class CreatePriceUpdateJobsTable extends Migration
{
    public function up()
    {
        Schema::create('price_update_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->nullable();           // temp NDJSON of resolved price rows (local disk)
            $table->string('backup_path')->nullable();         // NDJSON of previous price values while running
            $table->string('not_found_path')->nullable();      // NDJSON of product codes not found in the catalogue
            $table->string('original_file_name')->nullable();
            $table->string('status')->default('pending');      // pending, processing, completed, failed, cancelled

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('not_found_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->text('import_options')->nullable();         // JSON snapshot of price defaults (exchange rate, margin…)
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_update_jobs');
    }
}
