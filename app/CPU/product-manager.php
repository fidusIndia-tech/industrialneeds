<?php

namespace App\CPU;

use App\Model\Review;
use App\Model\Product;
use App\Model\OrderDetail;
use App\Model\Translation;
use App\Model\ShippingMethod;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;

class ProductManager
{
    public static function get_product($id)
    {
        return Product::active()->with(['rating'])->where('id', $id)->first();
    }

    /**
     * Apply a text search over name + product_code to a query builder.
     *
     * When the FULLTEXT index exists, uses it (boolean mode, prefix-wildcarded,
     * all terms required — better precision than the old space-split orWhere) and
     * orders by relevance. Otherwise (or for short part codes below the FULLTEXT
     * minimum token size) falls back to the original LIKE behaviour, so search
     * keeps working on MySQL builds where the FULLTEXT index can't be created.
     */
    public static function search_filter($builder, $name)
    {
        $terms = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($terms)) {
            return $builder;
        }

        $allLongEnough = true;
        foreach ($terms as $t) {
            if (mb_strlen($t) < 3) { // innodb_ft_min_token_size default
                $allLongEnough = false;
                break;
            }
        }

        if ($allLongEnough && self::fulltextAvailable()) {
            $booleanParts = [];
            foreach ($terms as $t) {
                $clean = trim(preg_replace('/[+\-*"()~<>@]/', ' ', $t));
                if ($clean !== '') {
                    $booleanParts[] = '+' . $clean . '*';
                }
            }
            $boolean = implode(' ', $booleanParts);
            if ($boolean !== '') {
                return $builder
                    ->whereRaw('MATCH(name, product_code) AGAINST(? IN BOOLEAN MODE)', [$boolean])
                    ->orderByRaw('MATCH(name, product_code) AGAINST(? IN BOOLEAN MODE) DESC', [$boolean]);
            }
        }

        $hasProductCode = \Schema::hasColumn('products', 'product_code');
        return $builder->where(function ($q) use ($terms, $hasProductCode) {
            foreach ($terms as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
                if ($hasProductCode) {
                    $q->orWhere('product_code', 'like', "%{$value}%");
                }
            }
        });
    }

    /**
     * Apply the best available search to a Product query builder: Meilisearch when
     * it's the active driver and reachable (relevance-ordered), otherwise the DB
     * FULLTEXT/LIKE helper. Keeps all existing scopes (active(), eager loads, etc.).
     */
    public static function apply_search($builder, $name)
    {
        $ids = self::engine_search_ids($name);
        if ($ids === null) {
            return self::search_filter($builder, $name); // engine off/unavailable
        }
        if (empty($ids)) {
            return $builder->whereRaw('1 = 0'); // engine ran, no matches
        }
        // Preserve Meilisearch relevance order. $ids are ints, safe to inline.
        $ordered = implode(',', $ids);
        return $builder->whereIn('id', $ids)->orderByRaw("FIELD(id, {$ordered})");
    }

    /**
     * Ranked product ids from the search engine, or NULL when the engine isn't the
     * active driver or a query fails (so callers fall back to DB search). Never
     * throws — a Meilisearch outage degrades to DB search, it doesn't break search.
     */
    public static function engine_search_ids($name, $limit = 1000)
    {
        if (config('scout.driver') !== 'meilisearch') {
            return null;
        }
        try {
            return Product::search((string) $name)->take($limit)->keys()
                ->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Meilisearch search failed; using DB fallback. ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Whether the products FULLTEXT index exists. Cached 1h so we don't run
     * SHOW INDEX on every search. MATCH against a missing index is a fatal SQL
     * error (it fires at execution, outside any builder-level try/catch), so this
     * gate must be reliable.
     */
    private static function fulltextAvailable(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember('products_fulltext_index_exists', 3600, function () {
            try {
                return !empty(DB::select("SHOW INDEX FROM products WHERE Key_name = 'products_name_code_fulltext'"));
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    public static function get_latest_products($limit = 10, $offset = 1)
    {
        $paginator = Product::active()->with(['rating'])->latest()->paginate($limit, ['*'], 'page', $offset);
        /*$paginator->count();*/
        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }

    public static function get_featured_products($limit = 10, $offset = 1)
    {
        //change review to ratting
        $paginator = Product::with(['rating'])->active()
            ->where('featured', 1)
            ->withCount(['order_details'])->orderBy('order_details_count', 'DESC')
            ->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }

    public static function get_top_rated_products($limit = 10, $offset = 1)
    {
        // $reviews = Review::with('product')
        //     ->whereHas('product', function ($query) {
        //         $query->active();
        //     })
        //     ->select('product_id', DB::raw('AVG(rating) as count'))
        //     ->groupBy('product_id')
        //     ->orderBy("count", 'desc')
        //     ->paginate($limit, ['*'], 'page', $offset);

        // $data = [];
        // foreach ($reviews as $review) {
        //     array_push($data, $review->product);
        // }
        //change review to ratting
        $reviews = Product::with(['rating'])->active()
            ->withCount(['reviews'])->orderBy('reviews_count', 'DESC')
            ->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $reviews->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $reviews
        ];
    }

    public static function get_best_selling_products($limit = 10, $offset = 1)
    {
        //change reviews to rattings
        $paginator = OrderDetail::with('product.rating')
            ->whereHas('product', function ($query) {
                $query->active();
            })
            ->select('product_id', DB::raw('COUNT(product_id) as count'))
            ->groupBy('product_id')
            ->orderBy("count", 'desc')
            ->paginate($limit, ['*'], 'page', $offset);

        $data = [];
        foreach ($paginator as $order) {
            array_push($data, $order->product);
        }

        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $data
        ];
    }

    public static function get_related_products($product_id)
    {
        $product = Product::find($product_id);
        return Product::active()->with(['rating'])->related($product->id)
            ->limit(10)
            ->get();
    }

    public static function search_products($name, $limit = 10, $offset = 1)
    {
        $name = base64_decode($name);

        $paginator = self::apply_search(Product::active()->with(['rating']), $name)
            ->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }
    public static function search_products_web($name, $limit = 10, $offset = 1)
    {
        $paginator = self::apply_search(Product::active()->with(['rating']), $name)
            ->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }

    public static function translated_product_search($name, $limit = 10, $offset = 1)
    {
        $name = base64_decode($name);
        $product_ids = Translation::where('translationable_type', 'App\Model\Product')
            ->where('key', 'name')
            ->where('value', 'like', "%{$name}%")
            ->pluck('translationable_id');

        $paginator = Product::WhereIn('id', $product_ids)->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }

    public static function translated_product_search_web($name, $limit = 10, $offset = 1)
    {
        $key = explode(' ', $name);
        $product_ids = Translation::where('translationable_type', 'App\Model\Product')
            ->where('key', 'name')
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('value', 'like', "%{$value}%");
                }
            })
            ->pluck('translationable_id');

        $paginator = Product::WhereIn('id', $product_ids)->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }

    public static function product_image_path($image_type)
    {
        $path = '';
        if ($image_type == 'thumbnail') {
            $path = asset('storage/app/public/product/thumbnail');
        } elseif ($image_type == 'product') {
            $path = asset('storage/app/public/product');
        }
        return $path;
    }

    public static function get_product_review($id)
    {
        $reviews = Review::where('product_id', $id)
            ->where('status', 1)->get();
        return $reviews;
    }

    public static function get_rating($reviews)
    {
        $rating5 = 0;
        $rating4 = 0;
        $rating3 = 0;
        $rating2 = 0;
        $rating1 = 0;
        foreach ($reviews as $key => $review) {
            if ($review->rating == 5) {
                $rating5 += 1;
            }
            if ($review->rating == 4) {
                $rating4 += 1;
            }
            if ($review->rating == 3) {
                $rating3 += 1;
            }
            if ($review->rating == 2) {
                $rating2 += 1;
            }
            if ($review->rating == 1) {
                $rating1 += 1;
            }
        }
        return [$rating5, $rating4, $rating3, $rating2, $rating1];
    }

    public static function get_overall_rating($reviews)
    {
        $totalRating = count($reviews);
        $rating = 0;
        foreach ($reviews as $key => $review) {
            $rating += $review->rating;
        }
        if ($totalRating == 0) {
            $overallRating = 0;
        } else {
            $overallRating = number_format($rating / $totalRating, 2);
        }

        return [$overallRating, $totalRating];
    }

    public static function get_shipping_methods($product)
    {
        if ($product['added_by'] == 'seller') {
            $methods = ShippingMethod::where(['creator_id' => $product['user_id']])->where(['status' => 1])->get();
            if ($methods->count() == 0) {
                $methods = ShippingMethod::where(['creator_type' => 'admin'])->where(['status' => 1])->get();
            }
        } else {
            $methods = ShippingMethod::where(['creator_type' => 'admin'])->where(['status' => 1])->get();
        }

        return $methods;
    }

    public static function get_seller_products($seller_id, $limit = 10, $offset = 1)
    {
        $paginator = Product::active()->with(['rating'])
            ->where(['user_id' => $seller_id, 'added_by' => 'seller'])
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);
        /*$paginator->count();*/
        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }

    public static function get_seller_all_products($seller_id, $limit = 10, $offset = 1)
    {
        $paginator = Product::with(['rating'])
            ->where(['user_id' => $seller_id, 'added_by' => 'seller'])
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);
        /*$paginator->count();*/
        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }

    public static function get_discounted_product($limit = 10, $offset = 1)
    {
        //change review to ratting
        $paginator = Product::with(['rating'])->active()->where('discount', '!=', 0)->latest()->paginate($limit, ['*'], 'page', $offset);
        return [
            'total_size' => $paginator->total(),
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'products' => $paginator->items()
        ];
    }
    public static function export_product_reviews($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $storage[] = [
                'product' => $item->product['name'] ?? '',
                'customer' => isset($item->customer) ? $item->customer->f_name .' '. $item->customer->l_name : '' ,
                'comment' => $item->comment,
                'rating' => $item->rating
            ];
        }
        return $storage;
    }
}
