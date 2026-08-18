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
 * Matching is case-insensitive, and the rewrite normalises the URL while it is
 * at it: an http:// link becomes https:// and a www. prefix is dropped, so
 * "http://www.industrialneeds.co/x" lands on "https://industrialsupply.in/x".
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

        // Matches the domain with or without a scheme and with or without www.
        $pattern = '#(https?://)?(www\.)?' . preg_quote($from, '#') . '#i';

        // Only character-ish columns can hold a URL; skip numeric/date/binary ones.
        $columns = DB::table('information_schema.columns')
            ->select('TABLE_NAME as table_name', 'COLUMN_NAME as column_name')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('DATA_TYPE', ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'])
            ->get();

        $totalRows = 0;
        $touched = [];
        $skipped = [];

        foreach ($columns as $column) {
            $table = $column->table_name;
            $field = $column->column_name;

            // LOWER() rather than a bare LIKE so the match does not depend on
            // the column's collation being case-insensitive.
            $rows = DB::table($table)
                ->whereRaw("LOWER(`{$field}`) LIKE ?", ['%' . strtolower($from) . '%'])
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $keys = $this->primaryKey($database, $table);

            if (!$keys) {
                // Without a primary key there is no safe way to address a single
                // row, so fall back to a blanket REPLACE(). That is exact-case
                // only, hence the warning.
                $skipped[] = "{$table}.{$field} ({$rows->count()})";
                $this->warn("  {$table}.{$field} — {$rows->count()} row(s), no primary key; exact-case REPLACE() only");

                if (!$dry) {
                    DB::statement(
                        "UPDATE `{$table}` SET `{$field}` = REPLACE(`{$field}`, ?, ?) WHERE `{$field}` LIKE ?",
                        [$from, $to, '%' . $from . '%']
                    );
                }
                continue;
            }

            $changed = 0;

            foreach ($rows as $row) {
                $old = $row->{$field};

                // MySQL's REPLACE() is case-sensitive, so do the rewrite in PHP
                // where the pattern can be case-insensitive.
                $new = preg_replace_callback($pattern, function ($m) use ($to) {
                    return ($m[1] ?? '') !== '' ? 'https://' . $to : $to;
                }, $old);

                if ($new === $old) {
                    continue;
                }

                $changed++;

                if (!$dry) {
                    $where = [];
                    foreach ($keys as $key) {
                        $where[$key] = $row->{$key};
                    }
                    DB::table($table)->where($where)->update([$field => $new]);
                }
            }

            if ($changed === 0) {
                continue;
            }

            $totalRows += $changed;
            $touched[] = "{$table}.{$field} ({$changed})";
            $this->line("  {$table}.{$field} — {$changed} row(s)");
        }

        if (!$touched && !$skipped) {
            $this->info('Nothing to do — no occurrences found.');
            return 0;
        }

        $this->info(($dry ? 'Would update ' : 'Updated ') . $totalRows . ' row(s) across ' . count($touched) . ' column(s).');

        if ($dry) {
            $this->comment('Re-run without --dry-run to apply.');
        }

        return 0;
    }

    /**
     * Primary key column names for a table, or an empty array when it has none.
     */
    private function primaryKey(string $database, string $table): array
    {
        return DB::table('information_schema.columns')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_KEY', 'PRI')
            ->orderBy('ORDINAL_POSITION')
            ->pluck('COLUMN_NAME')
            ->all();
    }
}
