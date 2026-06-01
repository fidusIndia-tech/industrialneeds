<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Generate a single self-contained HTML contact-sheet of product images so you can eyeball what the
 * fetcher attached (or any subset). Written under storage/app/public so it is web-accessible at
 * {APP_URL}storage/app/public/<file>, with image paths relative to that folder.
 */
class ProductImageGallery extends Command
{
    protected $signature = 'products:image-gallery
        {--status=fetched : Which products to show: fetched | needs_manual_review | any}
        {--source= : Filter by image_source (e.g. digikey)}
        {--brand= : Filter by brand name}
        {--limit=2000 : Max products to include}';

    protected $description = 'Build an HTML gallery of product images (default: the fetched ones) to spot-check results.';

    public function handle()
    {
        $status = (string) $this->option('status');
        $limit  = max(1, (int) $this->option('limit'));

        $q = DB::table('products as p')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->whereNotNull('p.product_code')->where('p.product_code', '!=', '')
            ->orderByDesc('p.updated_at');

        if ($status === 'fetched') {
            $q->where('p.image_status', 'fetched');
        } elseif ($status === 'needs_manual_review') {
            $q->where('p.image_status', 'needs_manual_review');
        } else { // any product that has a real (non-placeholder) image
            $q->where('p.thumbnail', '!=', 'def.png')->whereNotNull('p.thumbnail');
        }
        if (($src = trim((string) $this->option('source'))) !== '') {
            $q->where('p.image_source', $src);
        }
        if (($brand = trim((string) $this->option('brand'))) !== '') {
            $q->where('b.name', $brand);
        }

        $rows = $q->limit($limit)->get(['p.id', 'p.product_code', 'p.name', 'p.thumbnail', 'p.image_source', 'b.name as brand']);
        $this->info("Products: {$rows->count()} (status={$status})");
        if ($rows->isEmpty()) {
            return 0;
        }

        $cards = '';
        foreach ($rows as $r) {
            $thumb = $status === 'needs_manual_review' ? 'def.png' : ($r->thumbnail ?: 'def.png');
            $code  = htmlspecialchars($r->product_code, ENT_QUOTES);
            $name  = htmlspecialchars((string) $r->name, ENT_QUOTES);
            $brand = htmlspecialchars((string) $r->brand, ENT_QUOTES);
            $src   = htmlspecialchars((string) $r->image_source, ENT_QUOTES);
            // image paths are relative to storage/app/public/ where this HTML lives
            $cards .= "<figure><a href='product/thumbnail/{$thumb}' target='_blank'>"
                . "<img loading='lazy' src='product/thumbnail/{$thumb}' alt='{$code}'></a>"
                . "<figcaption><b>{$code}</b><span>#{$r->id} · {$brand}" . ($src ? " · {$src}" : '') . "</span>"
                . "<small>{$name}</small></figcaption></figure>\n";
        }

        $count = $rows->count();
        $html = "<!doctype html><html><head><meta charset='utf-8'><title>Product images — {$status} ({$count})</title>"
            . "<style>body{font-family:system-ui,Arial,sans-serif;margin:16px;background:#f5f6f8}"
            . "h1{font-size:18px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}"
            . "figure{margin:0;background:#fff;border:1px solid #e3e6ea;border-radius:8px;padding:8px;text-align:center}"
            . "img{width:100%;height:140px;object-fit:contain;background:#fff}"
            . "figcaption{font-size:12px;margin-top:6px}figcaption span{display:block;color:#667;font-size:11px}"
            . "figcaption small{display:block;color:#99a;font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}"
            . "</style></head><body><h1>Product images — {$status} ({$count})</h1><div class='grid'>{$cards}</div></body></html>";

        $rel = 'product_image_gallery_' . $status . '.html';
        Storage::disk('public')->put($rel, $html);

        $url = rtrim((string) config('app.url'), '/') . '/storage/app/public/' . $rel;
        $this->info('Gallery written. Open in your browser:');
        $this->line('  ' . $url);
        $this->line('  (file: ' . Storage::disk('public')->path($rel) . ')');
        return 0;
    }
}
