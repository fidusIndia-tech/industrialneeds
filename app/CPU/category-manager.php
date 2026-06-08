<?php

namespace App\CPU;

use App\Model\Category;
use App\Model\Product;
use Illuminate\Support\Facades\Cache;

class CategoryManager
{
    public static function parents()
    {
        $x = Category::with(['childes.childes'])->where('position', 0)->priority()->get();
        return $x;
    }

    /**
     * Top-level categories with two levels of children, for the front-end mega-menu.
     * Cached because it renders on EVERY front-end page and eager-loads a 3-level
     * tree (+ translations) — the menu changes rarely. Front-end category names use
     * the system default language, so a single version-busted key serves all
     * requests; Category model writes bump the version (see Category::bumpNavCache()).
     */
    public static function nav_tree($limit = 11)
    {
        $version = Cache::get('category_nav_version', 0);
        $key = 'category_nav_tree:v' . $version . ':' . $limit;

        return Cache::remember($key, 1800, function () use ($limit) {
            return Category::with(['childes.childes'])
                ->where('position', 0)->priority()->take($limit)->get();
        });
    }

    public static function child($parent_id)
    {
        $x = Category::where(['parent_id' => $parent_id])->get();
        return $x;
    }

    public static function products($category_id)
    {
        // Indexed product_categories pivot lookup (replaces an unindexable
        // category_ids LIKE '%"id"%' full table scan).
        return Product::active()
            ->inCategory($category_id)->get();
    }
}
