<?php

namespace App\Jobs;

use App\CPU\BulkImportProcessor;
use App\CPU\ProductImageFamilyService;
use App\Services\ImageProviders\DigiKeyImageProvider;
use App\Services\ImageProviders\Element14ImageProvider;
use App\Services\ImageProviders\ManualSearchImageProvider;
use App\Services\ImageProviders\NexarImageProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Background image fetch for one product. Tries to REUSE a curated family's already-downloaded image
 * first (no API call); otherwise asks the provider chain for an exact-match image, downloads + MIME-
 * validates it, stores a reusable family asset, and attaches it. If nothing trustworthy is found it
 * keeps the placeholder and flags the product for manual review. Never overwrites a real/manual image.
 */
class FetchProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 300];
    public $timeout = 90;

    /**
     * @param int        $productId
     * @param bool       $allowOverwrite  overwrite fetched/reused/real images (use with care)
     * @param array|null $onlyProviders   restrict the chain to these provider names (e.g. ['element14'])
     */
    public function __construct(public int $productId, public bool $allowOverwrite = false, public ?array $onlyProviders = null)
    {
    }

    public function handle(ProductImageFamilyService $family)
    {
        $p = DB::table('products')->where('id', $this->productId)->first();
        if (!$p) {
            return;
        }
        if (!$this->mayProcess($p)) {
            return; // protect real/manual images and already-done products
        }

        $brand = $p->brand_id ? DB::table('brands')->where('id', $p->brand_id)->value('name') : null;
        $fam = $family->familyKey($brand, $p->product_code, $p->name);

        DB::table('products')->where('id', $p->id)->update([
            'image_family_key'      => $fam['key'],
            'image_last_attempt_at' => now(),
        ]);

        // 1) Reuse an existing curated-family image — no provider/API call.
        if ($fam['curated']) {
            $asset = DB::table('product_image_assets')->where('family_key', $fam['key'])->first();
            if ($asset) {
                $this->attach($p->id, $asset->local_path, [$asset->local_path], 'reused', 'reused:' . $asset->source_type, (int) $asset->confidence, false);
                Log::info('FetchProductImageJob: reused family image', ['id' => $p->id, 'family' => $fam['key'], 'asset' => $asset->id]);
                return;
            }
        }

        // 2) Ask providers for an exact-match image.
        $quota = false;
        $definiteNoImage = false;
        $tried = [];  // providers that gave a definitive answer this run (not quota/skipped)
        foreach ($this->providers() as $provider) {
            $r = $provider->findImage((string) $p->product_code, $brand);

            if ($r['status'] === 'image') {
                $tried[] = $provider->name();
                $stats = ['images_from_zip' => 0, 'images_downloaded' => 0, 'failed_downloads' => 0, 'invalid_images' => 0];
                $img = BulkImportProcessor::resolveImages(
                    ['product_code' => $p->product_code, '_row' => $p->id, 'thumbnail_url' => $r['image_url']],
                    null, [], $stats
                );
                if ($img['has_image']) {
                    // Save a reusable canonical asset for curated families.
                    if ($fam['curated']) {
                        DB::table('product_image_assets')->updateOrInsert(
                            ['family_key' => $fam['key']],
                            [
                                'brand'       => $brand,
                                'source_url'  => $r['image_url'],
                                'local_path'  => $img['thumbnail'],
                                'source_type' => $provider->name(),
                                'confidence'  => $r['confidence'] ?? 90,
                                'updated_at'  => now(),
                                'created_at'  => now(),
                            ]
                        );
                    }
                    $this->attach($p->id, $img['thumbnail'], $img['images'], 'fetched', $provider->name(), $r['confidence'] ?? 90, false,
                        $this->mergeTried($p->image_providers_tried, $tried));
                    Log::info('FetchProductImageJob: fetched image', ['id' => $p->id, 'provider' => $provider->name(), 'family' => $fam['key']]);
                    return;
                }
                $definiteNoImage = true; // had a URL but it would not download/validate
            } elseif ($r['status'] === 'quota') {
                $quota = true; // not really tried — do not record, so it is retried later
            } elseif (in_array($r['status'], ['no_match', 'details_no_image'], true)) {
                $tried[] = $provider->name();
                $definiteNoImage = true;
            }
        }

        $mergedTried = $this->mergeTried($p->image_providers_tried, $tried);

        // 3) Outcome.
        if ($quota && !$definiteNoImage) {
            // Daily API quota is gone — reset to 'placeholder' so the dispatcher re-enqueues it next cycle.
            // Record any providers that DID answer this run (so a scoped retry can still skip them).
            DB::table('products')->where('id', $p->id)->update([
                'image_status' => 'placeholder', 'image_providers_tried' => $mergedTried, 'updated_at' => now(),
            ]);
            Log::info('FetchProductImageJob: quota exhausted, reset to placeholder', ['id' => $p->id, 'tried' => $mergedTried]);
            return;
        }

        DB::table('products')->where('id', $p->id)->update([
            'image_status'          => 'manual_review',
            'image_needs_review'    => true,
            'image_error'           => 'No trusted image found',
            'image_providers_tried' => $mergedTried,
            'updated_at'            => now(),
        ]);
        Log::warning('FetchProductImageJob: needs manual review', ['id' => $p->id, 'code' => $p->product_code, 'family' => $fam['key'], 'tried' => $mergedTried]);
    }

    /** Merge a comma-separated "tried providers" string with newly-tried names, de-duplicated. */
    private function mergeTried(?string $existing, array $new): ?string
    {
        $set = array_filter(array_map('trim', explode(',', (string) $existing)));
        $set = array_values(array_unique(array_merge($set, $new)));
        return $set ? implode(',', $set) : null;
    }

    /** Safety gate: never touch a real/manual image or an already-done product (unless allowed). */
    private function mayProcess($p): bool
    {
        if ($this->allowOverwrite) {
            return true;
        }
        if (in_array($p->image_status, ['fetched', 'reused'], true)) {
            return false; // already auto-attached
        }
        $hasRealImage = $p->thumbnail && $p->thumbnail !== 'def.png';
        if ($hasRealImage && empty($p->image_status)) {
            return false; // original catalogue / manual upload — protect it
        }
        return true;
    }

    /** Set the product image + status. Ensures the gallery copy exists when reusing. */
    private function attach(int $id, string $thumb, array $gallery, string $status, string $source, ?int $confidence, bool $needsReview, ?string $providersTried = null): void
    {
        foreach ($gallery as $g) {
            if ($g && $g !== 'def.png'
                && !Storage::disk('public')->exists('product/' . $g)
                && Storage::disk('public')->exists('product/thumbnail/' . $g)) {
                Storage::disk('public')->copy('product/thumbnail/' . $g, 'product/' . $g);
            }
        }
        $gallery = array_values(array_filter($gallery, fn ($g) => $g !== null && $g !== '')) ?: [$thumb];

        $data = [
            'thumbnail'          => $thumb,
            'images'             => json_encode($gallery),
            'image_status'       => $status,
            'image_source'       => $source,
            'image_confidence'   => $confidence,
            'image_needs_review' => $needsReview,
            'image_error'        => null,
            'updated_at'         => now(),
        ];
        if ($providersTried !== null) {
            $data['image_providers_tried'] = $providersTried;
        }
        DB::table('products')->where('id', $id)->update($data);
    }

    /** Available providers in priority order, optionally restricted by $this->onlyProviders. */
    private function providers(): array
    {
        $all = array_filter([
            new DigiKeyImageProvider(),    // exact-match, downloadable CDN, 1000/day
            new Element14ImageProvider(),  // Farnell/Newark — covers DigiKey's misses (downloadable CDN)
            // new NexarImageProvider(),   // disabled: free tier too small (~100/day). Class kept for later.
            new ManualSearchImageProvider(),
        ], fn ($p) => $p->isAvailable());

        if (!empty($this->onlyProviders)) {
            $only = array_map('strtolower', $this->onlyProviders);
            $all = array_filter($all, fn ($p) => in_array($p->name(), $only, true));
        }
        return array_values($all);
    }
}
