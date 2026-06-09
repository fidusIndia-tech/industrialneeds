<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * RFQ quote — the upgraded enquiry-only product request + admin response.
 * See database migration create_quotes_table for the lifecycle.
 */
class Quote extends Model
{
    protected $fillable = [
        'reference_no',
        'product_id',
        'customer_id',
        'customer_name',
        'phone_number',
        'email',
        'message',
        'requested_qty',
        'status',
        'quoted_unit_price',
        'quoted_qty',
        'quote_valid_until',
        'admin_note',
        'accept_token',
        'order_id',
    ];

    protected $casts = [
        'requested_qty' => 'integer',
        'quoted_qty' => 'integer',
        'quoted_unit_price' => 'float',
        'quote_valid_until' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
