<?php

namespace App\Model;

use App\CPU\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $casts = [
        'user_id' => 'integer',
        'brand_id' => 'integer',
        'min_qty' => 'integer',
        'published' => 'integer',
        'tax' => 'float',
        'unit_price' => 'float',
        'status' => 'integer',
        'discount' => 'float',
        'current_stock' => 'integer',
        'free_shipping' => 'integer',
        'featured_status' => 'integer',
        'refundable' => 'integer',
        'featured' => 'integer',
        'flash_deal' => 'integer',
        'seller_id' => 'integer',
        'purchase_price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'shipping_cost' => 'float',
        'multiply_qty' => 'integer',
        'temp_shipping_cost' => 'float',
        'is_shipping_cost_updated' => 'integer'
    ];

    public function translations()
    {
        return $this->morphMany('App\Model\Translation', 'translationable');
    }

    public function scopeActive($query)
    {
        return $query->whereHas('brand', function ($query) {
            $query->where(['status' => 1]);
        })->where(['status' => 1])->sellerApproved();
    }

    public function scopeSellerApproved($query)
    {
        $query->whereHas('seller', function ($query) {
            $query->where(['status' => 'approved']);
        })->orWhere(function ($query) {
            $query->where(['added_by' => 'admin', 'status' => 1])
                ->whereHas('brand', function ($query) {
                    $query->where(['status' => 1]);
                });
        });
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function scopeStatus($query)
    {
        return $query->where('featured_status', 1);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'seller_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'user_id');
    }

    public function rating()
    {
        return $this->hasMany(Review::class)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->groupBy('product_id');
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }


    public function order_delivered()
    {
        return $this->hasMany(OrderDetail::class, 'product_id')
            ->where('delivery_status', 'delivered');

    }

    public function wish_list()
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }

    /**
     * Restrict to products linked to a category via the indexed product_categories
     * pivot. $position null = any level; 1/2/3 = that level only. Uses whereExists so
     * it stays a single indexed query regardless of catalog size.
     */
    public function scopeInCategory($query, $categoryId, $position = null)
    {
        return $query->whereExists(function ($q) use ($categoryId, $position) {
            $q->select(DB::raw(1))
                ->from('product_categories')
                ->whereColumn('product_categories.product_id', 'products.id')
                ->where('product_categories.category_id', $categoryId);
            if ($position !== null) {
                $q->where('product_categories.position', $position);
            }
        });
    }

    /**
     * Products related to $productId: any OTHER product that shares at least one
     * category (via the pivot). Replaces the brittle exact category_ids JSON-string
     * equality match. Returns nothing if the source product has no categories.
     */
    public function scopeRelated($query, $productId)
    {
        return $query->where('id', '!=', $productId)
            ->whereExists(function ($q) use ($productId) {
                $q->select(DB::raw(1))
                    ->from('product_categories as pc')
                    ->whereColumn('pc.product_id', 'products.id')
                    ->whereIn('pc.category_id', function ($sub) use ($productId) {
                        $sub->select('category_id')
                            ->from('product_categories')
                            ->where('product_id', $productId);
                    });
            });
    }

    /**
     * Rebuild this product's rows in the product_categories pivot from its
     * category_ids JSON. Idempotent (delete + reinsert). Called from the saved
     * event and the catalog:sync-product-categories backfill command.
     */
    public function syncCategoryPivot(): void
    {
        DB::table('product_categories')->where('product_id', $this->id)->delete();

        $decoded = json_decode($this->category_ids, true);
        if (!is_array($decoded) || empty($decoded)) {
            return;
        }

        $rows = [];
        $seen = [];
        foreach ($decoded as $c) {
            if (!isset($c['id'])) {
                continue;
            }
            $categoryId = (int) $c['id'];
            if ($categoryId <= 0 || isset($seen[$categoryId])) {
                continue;
            }
            $seen[$categoryId] = true;
            $rows[] = [
                'product_id' => $this->id,
                'category_id' => $categoryId,
                'position' => isset($c['position']) ? (int) $c['position'] : 0,
            ];
        }
        if ($rows) {
            DB::table('product_categories')->insert($rows);
        }
    }

    public function getNameAttribute($name)
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/seller')) {
            return $name;
        }
        return $this->translations[0]->value ?? $name;
    }

    public function getDetailsAttribute($detail)
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/seller')) {
            return $detail;
        }
        return $this->translations[1]->value ?? $detail;
    }

    protected static function boot()
    {
        parent::boot();

        // Keep the product_categories pivot in sync whenever category_ids changes
        // (or on create). Bulk import inserts raw via DB::table and bypasses this, so
        // it must be followed by `catalog:sync-product-categories`.
        static::saved(function (Product $product) {
            if ($product->wasRecentlyCreated || $product->wasChanged('category_ids')) {
                $product->syncCategoryPivot();
            }
        });

        static::deleted(function (Product $product) {
            DB::table('product_categories')->where('product_id', $product->id)->delete();
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                if (strpos(url()->current(), '/api')) {
                    return $query->where('locale', App::getLocale());
                } else {
                    return $query->where('locale', Helpers::default_lang());
                }
            }, 'reviews'])->withCount('reviews');
        });
    }
}
