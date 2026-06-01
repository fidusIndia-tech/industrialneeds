<?php

namespace App\Console\Commands;

use App\CPU\ProductImageFamilyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time, safe normalisation of existing products into the image-pipeline model:
 *   - legacy 'needs_manual_review'  ->  'manual_review'
 *   - imported products still on the placeholder (def.png, no status)  ->  'placeholder'
 *   - backfill image_family_key for every product that has a product_code
 * The original catalogue (a real image with no image_status) is left completely untouched.
 * Idempotent + dry-run by default.
 */
class NormalizeImageStatus extends Command
{
    protected $signature = 'products:normalize-image-status {--execute : Apply changes (otherwise dry-run)} {--chunk=500}';

    protected $description = 'Backfill image_status (placeholder/manual_review) and image_family_key for existing products.';

    public function handle()
    {
        $apply = (bool) $this->option('execute');
        $chunk = max(50, (int) $this->option('chunk'));
        $family = new ProductImageFamilyService();

        // 1) legacy status rename
        $legacy = DB::table('products')->where('image_status', 'needs_manual_review')->count();
        $this->info("'needs_manual_review' -> 'manual_review': {$legacy}");
        if ($apply && $legacy) {
            DB::table('products')->where('image_status', 'needs_manual_review')->update(['image_status' => 'manual_review']);
        }

        // 2) placeholder status + family key backfill (only products with a product_code & no family key yet)
        $base = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->whereNull('image_family_key')->orderBy('id');

        $toPlaceholder = (clone $base)->where('thumbnail', 'def.png')->whereNull('image_status')->count();
        $needFamily = (clone $base)->count();
        $this->info("set 'placeholder' status: {$toPlaceholder}");
        $this->info("backfill image_family_key: {$needFamily}");

        if (!$apply) {
            $this->warn('Dry run — no changes. Re-run with --execute.');
            return 0;
        }

        $updated = 0;
        // chunkById because we set image_family_key, which is part of the WHERE filter.
        $base->chunkById($chunk, function ($rows) use ($family, &$updated) {
            $brandNames = DB::table('brands')->pluck('name', 'id');
            DB::transaction(function () use ($rows, $family, $brandNames, &$updated) {
                foreach ($rows as $p) {
                    $brand = $brandNames[$p->brand_id] ?? null;
                    $fam = $family->familyKey($brand, $p->product_code, $p->name);
                    $data = ['image_family_key' => $fam['key']];
                    // placeholder only when it's genuinely on the placeholder image with no status set
                    if ($p->thumbnail === 'def.png' && empty($p->image_status)) {
                        $data['image_status'] = 'placeholder';
                    }
                    DB::table('products')->where('id', $p->id)->update($data);
                    $updated++;
                }
            });
        }, 'id');

        $this->info("Updated {$updated} product(s). Legacy renamed: {$legacy}.");
        return 0;
    }
}
