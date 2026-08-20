<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Points the footer social icons at the Fidus India company profiles.
 *
 * The footer reads `social_medias` (admin-managed under Business Settings), so
 * these are ordinary rows: an admin can still edit, disable, or delete them from
 * the panel afterwards. Rows are matched on `name` — production shipped with a
 * placeholder `facebook` row pointing at the bare https://www.facebook.com/, so
 * an existing row has its link corrected rather than being left stale.
 */
class SeedFidusIndiaSocialMediaLinks extends Migration
{
    /**
     * Icons follow the same `fa fa-{name}` convention the admin store method uses,
     * and `name` doubles as the `sb-{name}` button class in the footer markup.
     */
    private $links = [
        'linkedin'  => 'https://in.linkedin.com/company/fidus-india-automation-pvt-ltd',
        'facebook'  => 'https://www.facebook.com/profile.php?id=61592887244405',
        'instagram' => 'https://www.instagram.com/fidusindiaautomation/',
    ];

    public function up()
    {
        $now = now();

        foreach ($this->links as $name => $link) {
            $existing = DB::table('social_medias')->where('name', $name)->first();

            if ($existing) {
                DB::table('social_medias')->where('id', $existing->id)->update([
                    'link'          => $link,
                    'icon'          => 'fa fa-' . $name,
                    'status'        => 1,
                    'active_status' => 1,
                    'updated_at'    => $now,
                ]);
                continue;
            }

            DB::table('social_medias')->insert([
                'name'          => $name,
                'link'          => $link,
                'icon'          => 'fa fa-' . $name,
                'status'        => 1,
                'active_status' => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    /**
     * Removes the rows only while they still hold the links set above; a link an
     * admin has since changed by hand is left alone. The pre-existing placeholder
     * facebook link is not restored — it was never a real profile.
     */
    public function down()
    {
        foreach ($this->links as $name => $link) {
            DB::table('social_medias')
                ->where('name', $name)
                ->where('link', $link)
                ->delete();
        }
    }
}
