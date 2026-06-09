<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RFQ MVP — the quote lifecycle table. Absorbs the enquiry-only product request AND
 * the admin's priced response in one row (single product, single response for the MVP).
 *
 * Lifecycle (status): requested -> quoted -> accepted|rejected|expired -> ordered
 *
 * The full schema is created up front so PR 2 (admin response) and PR 3 (accept -> order)
 * don't each need their own migration. PR 1 only populates the request-side columns.
 */
class CreateQuotesTable extends Migration
{
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable()->unique();
            $table->unsignedBigInteger('product_id')->nullable();

            // Requester (guest-friendly: customer_id is nullable, like the inquiry flow).
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->integer('requested_qty')->default(1);

            $table->enum('status', ['requested', 'quoted', 'accepted', 'rejected', 'expired', 'ordered'])
                ->default('requested');

            // Admin response (filled in PR 2).
            $table->decimal('quoted_unit_price', 24, 2)->nullable();
            $table->integer('quoted_qty')->nullable();
            $table->date('quote_valid_until')->nullable();
            $table->text('admin_note')->nullable();

            // Tokenised accept/reject link for guests (PR 3); order link on conversion.
            $table->uuid('accept_token')->nullable()->unique();
            $table->unsignedBigInteger('order_id')->nullable();

            $table->timestamps();

            $table->index(['status', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotes');
    }
}
