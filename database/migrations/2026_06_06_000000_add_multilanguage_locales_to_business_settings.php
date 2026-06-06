<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers additional UI locales (German, French, Spanish, Russian, Hindi, Chinese)
 * in the `business_settings` rows that drive the language switcher and the admin
 * language manager. Languages live in the DB (not config), so a migration is the
 * deploy-proof way to add them — running `php artisan migrate` registers them on
 * every environment. The matching resources/lang/{code}/messages.php files and flag
 * images ship with this branch.
 *
 * Idempotent: only appends a locale whose `code` is not already present, so it is
 * safe to re-run and will not clobber languages an admin added through the UI.
 */
class AddMultilanguageLocalesToBusinessSettings extends Migration
{
    /**
     * New locales to register. `code` must match both resources/lang/{code}/ and
     * the flag file public/assets/front-end/img/flags/{code}.png.
     */
    private array $locales = [
        ['name' => 'Deutsch',  'code' => 'de', 'direction' => 'ltr'],
        ['name' => 'Français', 'code' => 'fr', 'direction' => 'ltr'],
        ['name' => 'Español',  'code' => 'es', 'direction' => 'ltr'],
        ['name' => 'Русский',  'code' => 'ru', 'direction' => 'ltr'],
        ['name' => 'हिन्दी',     'code' => 'hi', 'direction' => 'ltr'],
        ['name' => '中文',      'code' => 'zh', 'direction' => 'ltr'],
    ];

    public function up(): void
    {
        $row = DB::table('business_settings')->where('type', 'language')->first();
        $languages = $row ? json_decode($row->value, true) : [];
        if (!is_array($languages)) {
            $languages = [];
        }

        $existingCodes = array_column($languages, 'code');
        $nextId = 0;
        foreach ($languages as $lang) {
            $nextId = max($nextId, (int) ($lang['id'] ?? 0));
        }

        foreach ($this->locales as $locale) {
            if (in_array($locale['code'], $existingCodes, true)) {
                continue;
            }
            $nextId++;
            $languages[] = [
                'id'        => $nextId,
                'name'      => $locale['name'],
                'direction' => $locale['direction'],
                'code'      => $locale['code'],
                'status'    => 1,      // active — shows in the front-end switcher
                'default'   => false,
            ];
            $existingCodes[] = $locale['code'];
        }

        DB::table('business_settings')->updateOrInsert(
            ['type' => 'language'],
            ['value' => json_encode($languages)]
        );

        // pnc_language is a flat list of codes used elsewhere; keep it in sync.
        $pncRow = DB::table('business_settings')->where('type', 'pnc_language')->first();
        $codes = $pncRow ? json_decode($pncRow->value, true) : [];
        if (!is_array($codes)) {
            $codes = [];
        }
        foreach ($this->locales as $locale) {
            if (!in_array($locale['code'], $codes, true)) {
                $codes[] = $locale['code'];
            }
        }
        DB::table('business_settings')->updateOrInsert(
            ['type' => 'pnc_language'],
            ['value' => json_encode(array_values($codes))]
        );
    }

    public function down(): void
    {
        $codesToRemove = array_column($this->locales, 'code');

        $row = DB::table('business_settings')->where('type', 'language')->first();
        if ($row) {
            $languages = json_decode($row->value, true) ?: [];
            $languages = array_values(array_filter($languages, function ($lang) use ($codesToRemove) {
                return !in_array($lang['code'] ?? null, $codesToRemove, true);
            }));
            DB::table('business_settings')->where('type', 'language')
                ->update(['value' => json_encode($languages)]);
        }

        $pncRow = DB::table('business_settings')->where('type', 'pnc_language')->first();
        if ($pncRow) {
            $codes = json_decode($pncRow->value, true) ?: [];
            $codes = array_values(array_filter($codes, function ($code) use ($codesToRemove) {
                return !in_array($code, $codesToRemove, true);
            }));
            DB::table('business_settings')->where('type', 'pnc_language')
                ->update(['value' => json_encode($codes)]);
        }
    }
}
