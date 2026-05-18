<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DisableOverseasCountry extends Migration
{
    /**
     * Soft-disable the "Overseas" country so it no longer appears in the
     * customer signup dropdown or admin shipping forms. We do NOT delete
     * the row because existing users may have users.country_id pointing
     * to it.
     */
    public function up()
    {
        if (!Schema::hasTable('countries') || !Schema::hasColumn('countries', 'status')) {
            return;
        }

        DB::table('countries')
            ->where('name', 'Overseas')
            ->update(['status' => 0]);
    }

    public function down()
    {
        if (!Schema::hasTable('countries') || !Schema::hasColumn('countries', 'status')) {
            return;
        }

        DB::table('countries')
            ->where('name', 'Overseas')
            ->update(['status' => 1]);
    }
}
