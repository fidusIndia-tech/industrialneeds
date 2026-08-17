<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-shot helper for the industrialneeds.co -> industrialsupply.in move.
 *
 * Source files were rewritten in the same change, but plenty of the old domain
 * also sits in the database: banner redirect URLs, the policy text stored in
 * business_settings, seller/shop links, order and support records. This sweeps
 * every text column in every table and rewrites matches in place.
 *
 * It is idempotent (a second run finds nothing) and safe to preview first:
 *
 *   php artisan domain:replace --dry-run
 *   php artisan domain:replace
 */
class ReplaceDomain extends Command
{
    protected $signature = 'domain:replace
                            {--from=industrialneeds.co : Old domain to search for}
                            {--to=industrialsupply.in : New domain to write}
                            {--dry-run : Only report what would change}';

    protected $description = 'Rewrite an old domain to a new one across every text column in the database';

    public function handle()
    {
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');
        $dry = (bool) $this->option('dry-run');

        if ($from === '' || $to === '' || $from === $to) {
            $this->error('--from and --to must both be set and differ.');
            return 1;
        }

        $database = DB::connection()->getDatabaseName();
        $this->info(($dry ? '[DRY RUN] ' : '') . "Replacing '{$from}' with '{$to}' in {$database}");

        // Only character-ish columns can hold a URL; skip numeric/date/binary ones.
        $columns = DB::table('information_schema.columns')
            ->select('TABLE_NAME as table_name', 'COLUMN_NAME as column_name')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('DATA_TYPE', ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'])
            ->get();

        $totalRows = 0;
        $touched = [];

        foreach ($columns as $column) {
            $table = $column->table_name;
            $field = $column->column_name;

            $matches = DB::table($table)
                ->where($field, 'like', '%' . $from . '%')
                ->count();

            if ($matches === 0) {
                continue;
            }

            $totalRows += $matches;
            $touched[] = "{$table}.{$field} ({$matches})";
            $this->line("  {$table}.{$field} — {$matches} row(s)");

            if (!$dry) {
                DB::statement(
                    "UPDATE `{$table}` SET `{$field}` = REPLACE(`{$field}`, ?, ?) WHERE `{$field}` LIKE ?",
                    [$from, $to, '%' . $from . '%']
                );
            }
        }

        if (!$touched) {
            $this->info('Nothing to do — no occurrences found.');
            return 0;
        }

        $this->info(($dry ? 'Would update ' : 'Updated ') . $totalRows . ' row(s) across ' . count($touched) . ' column(s).');

        if ($dry) {
            $this->comment('Re-run without --dry-run to apply.');
        }

        return 0;
    }
}
