<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Model\Brand;
use App\Model\Category;
use App\Model\Product;

class SitemapController extends Controller
{
    /**
     * Canonical public base URL for the live site. Generated sitemap URLs must
     * always point here (the production domain), independent of APP_URL which
     * differs between local/staging/production environments.
     */
    private const BASE_URL = 'https://industrialneeds.co';

    /**
     * Render a dynamically generated, SEO-valid sitemap.xml.
     *
     * Includes: homepage, all active/live products (clean /product/{slug} URLs),
     * all category listing pages, and all active brand listing pages. Excludes
     * admin/cart/checkout/login/wishlist/search/filter pages by design.
     */
    public function index()
    {
        // The XML response is corrupted by ANY output emitted before it. With
        // display_errors on, PHP deprecation/warning notices are echoed during
        // bootstrap, which (a) flushes default "text/html" headers so our XML
        // Content-Type is ignored and (b) prepends junk before "<?xml". index.php
        // buffers from the first line; here we suppress further error display and
        // discard whatever was buffered so the XML is emitted cleanly.
        @ini_set('display_errors', '0');
        if (ob_get_level() > 0 && ob_get_length() > 0) {
            @ob_clean();
        }

        $base = self::BASE_URL;

        // Drop the translate global scope: the sitemap only needs id/slug/dates,
        // not eager-loaded translations/reviews, so this keeps the query lean.
        $categories = Category::withoutGlobalScope('translate')
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get();

        $brands = Brand::withoutGlobalScope('translate')
            ->where('status', 1)
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get();

        // active() = published + brand active + seller approved (the exact scope
        // the public product page uses), so only live products are listed.
        $products = Product::withoutGlobalScope('translate')
            ->active()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select('id', 'slug', 'updated_at')
            ->orderBy('id')
            ->get();

        return response()
            ->view('sitemap', compact('base', 'categories', 'brands', 'products'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
