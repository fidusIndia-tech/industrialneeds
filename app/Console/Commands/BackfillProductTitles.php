<?php

namespace App\Console\Commands;

use App\CPU\BulkImportHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill: clean up already-imported product titles so the name holds ONLY the part/model number,
 * moving the leftover descriptive text into the product details. Reuses the exact same logic the
 * bulk importer now uses (App\CPU\BulkImportHelper::extractProductTitleAndDescription).
 *
 * Scope: by default only products that have a product_code (the bulk-imported set) are considered,
 * and a row is only changed when a part number is CONFIDENTLY detected. The original catalogue
 * (products without a product_code) and already-clean names are left untouched.
 *
 * Safety: dry-run by default. With --execute it first writes a JSON backup of every row it will
 * change (id, name, details) so the change is fully reversible via --restore=<path>.
 */
class BackfillProductTitles extends Command
{
    protected $signature = 'products:backfill-titles
        {--execute : Actually apply the changes (otherwise dry-run)}
        {--limit=0 : Only process the first N matching products (0 = all)}
        {--brand= : Restrict to a single brand name (e.g. Telemecanique)}
        {--chunk=500 : Rows read per batch}
        {--restore= : Restore names/details from a backup JSON file produced by a previous --execute run}';

    protected $description = 'Clean imported product titles to just the part number; move the rest into details. Dry-run by default.';

    public function handle()
    {
        if ($restorePath = $this->option('restore')) {
            return $this->restore($restorePath);
        }

        $apply = (bool) $this->option('execute');
        $limit = (int) $this->option('limit');
        $chunk = max(50, (int) $this->option('chunk'));
        $brandFilter = trim((string) $this->option('brand'));

        // Brand id => name (raw, no model accessors/scopes).
        $brandNames = DB::table('brands')->pluck('name', 'id')->toArray();

        // Only bulk-imported rows carry a product_code; the original catalogue does not.
        $query = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->orderBy('id');

        if ($brandFilter !== '') {
            $brandId = array_search(mb_strtolower($brandFilter), array_map('mb_strtolower', $brandNames), true);
            if ($brandId === false) {
                $this->error("Brand '{$brandFilter}' not found.");
                return 1;
            }
            $query->where('brand_id', $brandId);
        }

        $candidateTotal = (clone $query)->count();
        $this->info("Candidate products (with product_code" . ($brandFilter !== '' ? ", brand={$brandFilter}" : '') . "): {$candidateTotal}");
        if ($candidateTotal === 0) {
            return 0;
        }

        $scanned = 0;
        $willChange = 0;
        $samples = [];
        $backup = [];   // [{id, name, details}] of rows we change (for --execute)
        $updatedCount = 0;

        // Process one batch of rows (read-only in dry-run; writes in --execute).
        $handleRows = function ($rows) use (&$scanned, &$willChange, &$samples, &$backup, &$updatedCount, $brandNames, $apply) {
            foreach ($rows as $p) {
                $scanned++;
                $brandName = $brandNames[$p->brand_id] ?? null;
                $info = BulkImportHelper::extractProductTitleAndDescription($p->name, $brandName, $p->product_code);

                if (!$info['changed'] || $info['product_name'] === $p->name) {
                    continue; // already clean / no confident part number → leave as-is
                }

                $willChange++;
                if (count($samples) < 15) {
                    $samples[] = ['id' => $p->id, 'from' => $p->name, 'to' => $info['product_name']];
                }

                if ($apply) {
                    $backup[] = ['id' => $p->id, 'name' => $p->name, 'details' => $p->details];
                    DB::table('products')->where('id', $p->id)->update([
                        'name'       => $info['product_name'],
                        'details'    => $this->mergeDetails($p->details, $info['description']),
                        'updated_at' => now(),
                        // slug intentionally left unchanged so existing product URLs keep working
                    ]);
                    $updatedCount++;
                }
            }
        };

        if ($limit > 0) {
            // Bounded run (handy for testing on a subset).
            $rows = (clone $query)->limit($limit)->get();
            $apply ? DB::transaction(fn () => $handleRows($rows)) : $handleRows($rows);
        } else {
            // Full run in chunks; each batch is its own transaction on --execute.
            $query->chunk($chunk, function ($rows) use ($apply, $handleRows) {
                $apply ? DB::transaction(fn () => $handleRows($rows)) : $handleRows($rows);
            });
        }

        // Show a before → after sample.
        if ($samples) {
            $this->line('');
            $this->info('Sample changes:');
            $this->table(['ID', 'Old name', 'New name'],
                array_map(fn ($s) => [$s['id'], mb_strimwidth($s['from'], 0, 60, '…'), $s['to']], $samples));
        }

        $this->info("Scanned: {$scanned}   Will change: {$willChange}   Unchanged: " . ($scanned - $willChange));

        if (!$apply) {
            $this->warn('Dry run — no changes made. Re-run with --execute to apply.');
            return 0;
        }

        // Persist the backup so the change is reversible.
        Storage::disk('local')->makeDirectory('backups');
        $backupFile = 'backups/product_titles_backup_' . now()->format('Ymd_His') . '.json';
        Storage::disk('local')->put($backupFile, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Updated {$updatedCount} product(s).");
        $this->info('Backup written to: ' . Storage::disk('local')->path($backupFile));
        $this->line('Restore with:  php artisan products:backfill-titles --restore="' . Storage::disk('local')->path($backupFile) . '"');
        return 0;
    }

    /** Merge existing details with the extracted descriptive block (keeps existing, appends new). */
    private function mergeDetails(?string $existing, ?string $extracted): string
    {
        $existing = trim((string) $existing);
        $extracted = trim((string) $extracted);
        if ($extracted === '') return $existing;
        if ($existing === '') return $extracted;
        return $existing . "\n\n" . $extracted;
    }

    /** Restore name/details for every row recorded in a backup JSON file. */
    private function restore(string $path): int
    {
        if (!is_file($path)) {
            $this->error("Backup file not found: {$path}");
            return 1;
        }
        $rows = json_decode((string) file_get_contents($path), true);
        if (!is_array($rows) || empty($rows)) {
            $this->error('Backup file is empty or invalid.');
            return 1;
        }
        if (!$this->confirm('Restore ' . count($rows) . ' product(s) name/details from this backup?', true)) {
            $this->info('Aborted.');
            return 0;
        }
        $restored = 0;
        foreach (array_chunk($rows, 500) as $batch) {
            DB::transaction(function () use ($batch, &$restored) {
                foreach ($batch as $r) {
                    if (!isset($r['id'])) continue;
                    DB::table('products')->where('id', $r['id'])->update([
                        'name'       => $r['name'] ?? '',
                        'details'    => $r['details'] ?? '',
                        'updated_at' => now(),
                    ]);
                    $restored++;
                }
            });
        }
        $this->info("Restored {$restored} product(s).");
        return 0;
    }
}
