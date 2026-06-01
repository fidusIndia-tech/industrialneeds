<?php

namespace App\Console\Commands;

use App\CPU\BulkImportHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill SEO meta_title / meta_description for already-imported products, using the same logic
 * as the importer (App\CPU\BulkImportHelper::buildSeoMeta).
 *
 *   meta_title       = "Brand PartNumber"
 *   meta_description = "Brand PartNumber - Type. Series X."
 *
 * Sources per product:
 *   - part number = product_code (authoritative SKU; also the product name after the title backfill)
 *   - brand       = brands.name via brand_id
 *   - type        = the "Product Type:" line in details (added by the title backfill), else the
 *                   product's leaf category name
 *   - series      = the "Series:" line in details, if present
 *
 * Scope: only products with a product_code (the bulk-imported set). By default only rows whose
 * meta_title is empty are filled (pass --force to overwrite existing meta).
 *
 * Safety: dry-run by default; --execute writes a JSON backup (id, meta_title, meta_description)
 * that can be restored with --restore=<path>.
 */
class BackfillProductMeta extends Command
{
    protected $signature = 'products:backfill-meta
        {--execute : Actually apply the changes (otherwise dry-run)}
        {--force : Overwrite existing meta (default only fills empty meta_title)}
        {--limit=0 : Only process the first N matching products (0 = all)}
        {--brand= : Restrict to a single brand name (e.g. Telemecanique)}
        {--chunk=500 : Rows read per batch}
        {--restore= : Restore meta from a backup JSON file produced by a previous --execute run}';

    protected $description = 'Fill meta_title / meta_description for imported products. Dry-run by default.';

    public function handle()
    {
        if ($restorePath = $this->option('restore')) {
            return $this->restore($restorePath);
        }

        $apply = (bool) $this->option('execute');
        $force = (bool) $this->option('force');
        $limit = (int) $this->option('limit');
        $chunk = max(50, (int) $this->option('chunk'));
        $brandFilter = trim((string) $this->option('brand'));

        $brandNames = DB::table('brands')->pluck('name', 'id')->toArray();
        $catNames   = DB::table('categories')->pluck('name', 'id')->toArray();

        // Only bulk-imported rows carry a product_code.
        $query = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->orderBy('id');

        if (!$force) {
            // Backfill = only fill rows that don't already have a meta_title.
            $query->where(function ($q) {
                $q->whereNull('meta_title')->orWhere('meta_title', '');
            });
        }

        if ($brandFilter !== '') {
            $brandId = array_search(mb_strtolower($brandFilter), array_map('mb_strtolower', $brandNames), true);
            if ($brandId === false) {
                $this->error("Brand '{$brandFilter}' not found.");
                return 1;
            }
            $query->where('brand_id', $brandId);
        }

        $candidateTotal = (clone $query)->count();
        $this->info("Candidate products: {$candidateTotal}" . ($force ? ' (--force: includes rows with existing meta)' : ' (empty meta_title only)'));
        if ($candidateTotal === 0) {
            $this->info('Nothing to backfill.');
            return 0;
        }

        $scanned = 0;
        $willChange = 0;
        $samples = [];
        $backup = [];
        $updatedCount = 0;

        $handleRows = function ($rows) use (&$scanned, &$willChange, &$samples, &$backup, &$updatedCount, $brandNames, $catNames, $apply) {
            foreach ($rows as $p) {
                $scanned++;
                $brand = $brandNames[$p->brand_id] ?? null;
                $type = $this->typeFor($p, $catNames);
                $series = $this->seriesFor($p);

                $meta = BulkImportHelper::buildSeoMeta($p->product_code, $brand, $type, $series);
                if ($meta['meta_title'] === '' && $meta['meta_description'] === '') {
                    continue; // nothing to write
                }

                $willChange++;
                if (count($samples) < 12) {
                    $samples[] = ['id' => $p->id, 'title' => $meta['meta_title'], 'desc' => $meta['meta_description']];
                }

                if ($apply) {
                    $backup[] = ['id' => $p->id, 'meta_title' => $p->meta_title, 'meta_description' => $p->meta_description];
                    DB::table('products')->where('id', $p->id)->update([
                        'meta_title'       => $meta['meta_title'],
                        'meta_description' => $meta['meta_description'],
                        'updated_at'       => now(),
                    ]);
                    $updatedCount++;
                }
            }
        };

        if ($limit > 0) {
            $rows = (clone $query)->limit($limit)->get();
            $apply ? DB::transaction(fn () => $handleRows($rows)) : $handleRows($rows);
        } else {
            // chunkById (not chunk) because --execute mutates meta_title, which is part of the WHERE
            // filter; offset-based chunk() would skip rows as they drop out of the result set.
            $query->chunkById($chunk, function ($rows) use ($apply, $handleRows) {
                $apply ? DB::transaction(fn () => $handleRows($rows)) : $handleRows($rows);
            }, 'id');
        }

        if ($samples) {
            $this->line('');
            $this->info('Sample meta:');
            $this->table(['ID', 'meta_title', 'meta_description'],
                array_map(fn ($s) => [$s['id'], $s['title'], mb_strimwidth($s['desc'], 0, 70, '…')], $samples));
        }

        $this->info("Scanned: {$scanned}   Will change: {$willChange}");

        if (!$apply) {
            $this->warn('Dry run — no changes made. Re-run with --execute to apply.');
            return 0;
        }

        Storage::disk('local')->makeDirectory('backups');
        $backupFile = 'backups/product_meta_backup_' . now()->format('Ymd_His') . '.json';
        Storage::disk('local')->put($backupFile, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Updated {$updatedCount} product(s).");
        $this->info('Backup written to: ' . Storage::disk('local')->path($backupFile));
        $this->line('Restore with:  php artisan products:backfill-meta --restore="' . Storage::disk('local')->path($backupFile) . '"');
        return 0;
    }

    /** Type = "Product Type:" line from details, else the product's leaf category name. */
    private function typeFor($product, array $catNames): ?string
    {
        if (preg_match('/^Product Type:\s*(.+)$/mu', (string) $product->details, $m)) {
            $t = trim($m[1]);
            if ($t !== '') return $t;
        }
        // Fall back to the most specific (highest position) category in category_ids.
        $ids = json_decode((string) $product->category_ids, true);
        if (is_array($ids) && $ids) {
            usort($ids, fn ($a, $b) => (int)($b['position'] ?? 0) <=> (int)($a['position'] ?? 0));
            $leafId = (int)($ids[0]['id'] ?? 0);
            if ($leafId && isset($catNames[$leafId])) {
                return $catNames[$leafId];
            }
        }
        return null;
    }

    /** Series = "Series:" line from details, if the title backfill recorded one. */
    private function seriesFor($product): ?string
    {
        if (preg_match('/^Series:\s*(.+)$/mu', (string) $product->details, $m)) {
            $s = trim($m[1]);
            return $s !== '' ? $s : null;
        }
        return null;
    }

    /** Restore meta for every row recorded in a backup JSON file. */
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
        if (!$this->confirm('Restore meta for ' . count($rows) . ' product(s) from this backup?', true)) {
            $this->info('Aborted.');
            return 0;
        }
        $restored = 0;
        foreach (array_chunk($rows, 500) as $batch) {
            DB::transaction(function () use ($batch, &$restored) {
                foreach ($batch as $r) {
                    if (!isset($r['id'])) continue;
                    DB::table('products')->where('id', $r['id'])->update([
                        'meta_title'       => $r['meta_title'] ?? null,
                        'meta_description' => $r['meta_description'] ?? null,
                        'updated_at'       => now(),
                    ]);
                    $restored++;
                }
            });
        }
        $this->info("Restored {$restored} product(s).");
        return 0;
    }
}
