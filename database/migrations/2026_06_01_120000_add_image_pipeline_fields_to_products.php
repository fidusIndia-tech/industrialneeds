<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Background image-pipeline fields on products. All additive + nullable — no existing data changes.
 * (image_status and image_source already exist from an earlier migration.)
 */
class AddImagePipelineFieldsToProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('image_confidence')->nullable()->after('image_source'); // 0-100
            $table->string('image_family_key')->nullable()->after('image_confidence');
            $table->boolean('image_needs_review')->default(false)->after('image_family_key');
            $table->timestamp('image_last_attempt_at')->nullable()->after('image_needs_review');
            $table->text('image_error')->nullable()->after('image_last_attempt_at');
            $table->index('image_family_key');
            $table->index('image_status');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['image_family_key']);
            $table->dropIndex(['image_status']);
            $table->dropColumn(['image_confidence', 'image_family_key', 'image_needs_review', 'image_last_attempt_at', 'image_error']);
        });
    }
}
