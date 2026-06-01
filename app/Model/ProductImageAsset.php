<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * One canonical downloaded image per product family (see App\CPU\ProductImageFamilyService).
 * Lets many products in the same tight family reuse the same image with no extra API call.
 */
class ProductImageAsset extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'confidence' => 'integer',
    ];
}
