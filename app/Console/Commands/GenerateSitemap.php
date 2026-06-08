<?php

namespace App\Console\Commands;

use App\Model\Brand;
use App\Model\Category;
use App\Model\Product;
use Illuminate\Console\Command;

/**
 * Generates a STATIC sitemap to public/ so Apache can serve it directly without
 * booting Laravel or touching the DB on every request — the dynamic version was
 * disabled (commit 2d7777d) precisely because per-request generation caused high
 * PHP/DB load on the live site.
 *
 * Output:
 *   public/sitemap.xml            — sitemap index (the URL you submit to Google)
 *   public/sitemap-pages.xml      — homepage + category + brand listing pages
 *   public/sitemap-products-N.xml — products, chunked at MAX_URLS_PER_FILE
 *
 * Run on a schedule (e.g. daily) or after large catalog imports:
 *   php artisan sitemap:generate
 */
class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate a static sitemap index + child sitemaps into public/';

    /** Sitemap spec caps a single file at 50,000 URLs; stay comfortably under. */
    private const MAX_URLS_PER_FILE = 40000;

    public function handle(): int
    {
        // Canonical public base URL for generated links. Must point at the live
        // production domain regardless of APP_URL (which differs per environment).
        $base = rtrim((string) env('SITEMAP_BASE_URL', 'https://industrialneeds.co'), '/');
        // This app is a CodeCanyon-flattened layout: the front controller lives at
        // the project root (root index.php), so /sitemap.xml is served from base_path(),
        // NOT public_path(). Writing here lets Apache serve the file directly.
        $publicPath = base_path();
        $today = date('Y-m-d');

        // Remove any product sitemap files from a previous (possibly larger) run
        // so stale chunks don't linger after the catalog shrinks.
        foreach (glob($publicPath . DIRECTORY_SEPARATOR . 'sitemap-products-*.xml') ?: [] as $old) {
            @unlink($old);
        }

        $childSitemaps = [];

        // --- Pages: homepage + category + brand listings ------------------------
        $urls = [];
        $urls[] = $this->urlEntry($base . '/', $today, 'daily', '1.0');

        Category::withoutGlobalScope('translate')
            ->select('id', 'updated_at')->orderBy('id')
            ->chunk(2000, function ($categories) use (&$urls, $base) {
                foreach ($categories as $category) {
                    $loc = $base . '/products?id=' . $category->id . '&data_from=category&page=1';
                    $urls[] = $this->urlEntry($loc, optional($category->updated_at)->format('Y-m-d'), 'weekly', '0.8');
                }
            });

        Brand::withoutGlobalScope('translate')->where('status', 1)
            ->select('id', 'updated_at')->orderBy('id')
            ->chunk(2000, function ($brands) use (&$urls, $base) {
                foreach ($brands as $brand) {
                    $loc = $base . '/products?id=' . $brand->id . '&data_from=brand&page=1';
                    $urls[] = $this->urlEntry($loc, optional($brand->updated_at)->format('Y-m-d'), 'weekly', '0.6');
                }
            });

        $this->writeUrlSet($publicPath . '/sitemap-pages.xml', $urls);
        $childSitemaps[] = $base . '/sitemap-pages.xml';
        $this->info('Wrote sitemap-pages.xml (' . count($urls) . ' urls)');

        // --- Products: chunked across multiple files ----------------------------
        $fileIndex = 0;
        $buffer = [];
        $productCount = 0;

        $flush = function () use (&$buffer, &$fileIndex, &$childSitemaps, $publicPath, $base) {
            if (empty($buffer)) {
                return;
            }
            $fileIndex++;
            $name = 'sitemap-products-' . $fileIndex . '.xml';
            $this->writeUrlSet($publicPath . '/' . $name, $buffer);
            $childSitemaps[] = $base . '/' . $name;
            $this->info('Wrote ' . $name . ' (' . count($buffer) . ' urls)');
            $buffer = [];
        };

        // active() = published + brand active + seller approved — exactly the scope
        // the public product page uses, so only live products get indexed.
        Product::withoutGlobalScope('translate')
            ->active()
            ->whereNotNull('slug')->where('slug', '!=', '')
            ->select('id', 'slug', 'updated_at')
            ->orderBy('id')
            ->chunkById(2000, function ($products) use (&$buffer, &$productCount, $base, $today, $flush) {
                foreach ($products as $product) {
                    $loc = $base . '/product/' . $product->slug;
                    $buffer[] = $this->urlEntry($loc, optional($product->updated_at)->format('Y-m-d') ?: $today, 'weekly', '0.7');
                    $productCount++;
                    if (count($buffer) >= self::MAX_URLS_PER_FILE) {
                        $flush();
                    }
                }
            });
        $flush();

        // --- Sitemap index ------------------------------------------------------
        $this->writeIndex($publicPath . '/sitemap.xml', $childSitemaps, $today);
        $this->info('Wrote sitemap.xml index (' . count($childSitemaps) . ' child sitemaps, ' . $productCount . ' products)');

        return self::SUCCESS;
    }

    private function urlEntry(string $loc, ?string $lastmod, string $changefreq, string $priority): string
    {
        $xml = "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
        if ($lastmod) {
            $xml .= '    <lastmod>' . $lastmod . "</lastmod>\n";
        }
        $xml .= '    <changefreq>' . $changefreq . "</changefreq>\n";
        $xml .= '    <priority>' . $priority . "</priority>\n";
        $xml .= "  </url>\n";

        return $xml;
    }

    private function writeUrlSet(string $path, array $urlEntries): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode('', $urlEntries);
        $xml .= '</urlset>' . "\n";
        file_put_contents($path, $xml);
    }

    private function writeIndex(string $path, array $childSitemaps, string $today): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($childSitemaps as $loc) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            $xml .= '    <lastmod>' . $today . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }
        $xml .= '</sitemapindex>' . "\n";
        file_put_contents($path, $xml);
    }
}
