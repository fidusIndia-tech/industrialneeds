<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Model\Brand;
use App\Model\Category;
use App\Model\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Canonical public base URL for the live site. Generated sitemap URLs must
     * always point here (the production domain), independent of APP_URL which
     * differs between local/staging/production environments.
     */
    private const BASE_URL = 'https://industrialneeds.co';

    /**
     * Output a dynamically generated, SEO-valid sitemap.xml.
     *
     * Includes: homepage, all active/live products, all category listing pages,
     * and all active brand listing pages. Excludes admin/cart/checkout/login/
     * wishlist/search/filter pages by design (only known public, indexable URLs).
     */
    public function index()
    {
        // IMPORTANT: an XML response is corrupted by ANY stray output emitted
        // before it. If display_errors is on (common in production) a single PHP
        // notice/warning is echoed first, which (a) forces PHP to flush default
        // "text/html" headers so our application/xml header is ignored, and
        // (b) prepends junk before "<?xml". The browser then renders the body as
        // HTML/plain text with tags stripped. Suppress error display for this
        // route and discard any already-buffered output so the XML is delivered
        // cleanly with the correct Content-Type.
        @ini_set('display_errors', '0');
        if (ob_get_level() > 0 && ob_get_length() > 0) {
            @ob_clean();
        }

        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            return $this->build();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function build(): string
    {
        $urls = [];

        // 1. Homepage
        $urls[] = $this->url(self::BASE_URL . '/', null, 'daily', '1.0');

        // 2. Category listing pages
        Category::select('id', 'updated_at')->orderBy('id')->chunk(500, function ($categories) use (&$urls) {
            foreach ($categories as $category) {
                $loc = self::BASE_URL . '/products?id=' . $category->id . '&data_from=category&page=1';
                $urls[] = $this->url($loc, $category->updated_at, 'weekly', '0.8');
            }
        });

        // 3. Brand listing pages (only active brands)
        Brand::where('status', 1)->select('id', 'updated_at')->orderBy('id')->chunk(500, function ($brands) use (&$urls) {
            foreach ($brands as $brand) {
                $loc = self::BASE_URL . '/products?id=' . $brand->id . '&data_from=brand&page=1';
                $urls[] = $this->url($loc, $brand->updated_at, 'weekly', '0.6');
            }
        });

        // 4. Active/live product detail pages (same scope used on the public site).
        //    Uses the clean canonical /product/{slug} URL.
        Product::active()->select('id', 'slug', 'updated_at')->orderBy('id')->chunk(500, function ($products) use (&$urls) {
            foreach ($products as $product) {
                if (empty($product->slug)) {
                    continue;
                }
                $loc = self::BASE_URL . '/product/' . $product->slug;
                $urls[] = $this->url($loc, $product->updated_at, 'weekly', '0.7');
            }
        });

        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $body .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $body .= implode('', $urls);
        $body .= '</urlset>' . "\n";

        return $body;
    }

    /**
     * Render a single <url> entry with XML-escaped values.
     */
    private function url(string $loc, $lastmod, string $changefreq, string $priority): string
    {
        $entry = "    <url>\n";
        $entry .= '        <loc>' . $this->escape($loc) . "</loc>\n";

        if (!empty($lastmod)) {
            $date = $lastmod instanceof Carbon ? $lastmod : Carbon::parse($lastmod);
            $entry .= '        <lastmod>' . $date->format('Y-m-d') . "</lastmod>\n";
        }

        $entry .= '        <changefreq>' . $changefreq . "</changefreq>\n";
        $entry .= '        <priority>' . $priority . "</priority>\n";
        $entry .= "    </url>\n";

        return $entry;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
