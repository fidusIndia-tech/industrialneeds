<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads captured by the on-site scripted chatbot. A row is created the moment a visitor opens
 * the chat (status=engaged) and is enriched as they answer; once a phone number is captured it
 * becomes a callable lead (status=lead) and the sales team is alerted.
 */
class CreateChatLeadsTable extends Migration
{
    public function up()
    {
        Schema::create('chat_leads', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();   // groups one conversation
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('intent')->nullable();         // first choice: price / availability / technical / other
            $table->string('product')->nullable();        // product or part number asked about
            $table->string('quantity')->nullable();
            $table->text('message')->nullable();          // free-text note
            $table->json('transcript')->nullable();       // full Q&A log
            $table->string('page_url')->nullable();       // where the chat was opened
            $table->enum('status', ['engaged', 'lead', 'contacted', 'closed'])->default('engaged')->index();
            $table->boolean('notified')->default(false);  // alert sent for this lead
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_leads');
    }
}
