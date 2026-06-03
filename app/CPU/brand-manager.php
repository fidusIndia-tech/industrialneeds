<?php

namespace App\CPU;

use App\Model\Brand;
use App\Model\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BrandManager
{
    public static function get_brands()
    {
        // Rendered in the site header on every page. Cached + counts attached via
        // a single grouped query (see attach_product_counts) instead of a
        // per-brand correlated subquery, which was a major page-load bottleneck.
        return Cache::remember('brand_manager_all_brands_' . Helpers::default_lang(), now()->addMinutes(30), function () {
            return self::attach_product_counts(Brand::latest()->get());
        });
    }

    public static function get_products($brand_id)
    {
        return Helpers::product_data_formatting(Product::active()->where(['brand_id' => $brand_id])->get(), true);
    }

    public static function get_active_brands()
    {
        return Cache::remember('brand_manager_active_brands_' . Helpers::default_lang(), now()->addMinutes(30), function () {
            return self::attach_product_counts(Brand::active()->latest()->get());
        });
    }

    /**
     * Attach brand_products_count (count of active products per brand) using one
     * grouped query rather than a correlated subquery per brand. Preserves the
     * attribute name the views read: $brand['brand_products_count'].
     */
    private static function attach_product_counts($brands)
    {
        $counts = Product::withoutGlobalScope('translate')->active()
            ->select('brand_id', DB::raw('count(*) as aggregate'))
            ->groupBy('brand_id')
            ->pluck('aggregate', 'brand_id');

        $brands->each(function ($brand) use ($counts) {
            $brand->brand_products_count = (int) ($counts[$brand->id] ?? 0);
        });

        return $brands;
    }
}
