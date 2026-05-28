<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'status',
        'note',
        'courier_name',
        'tracking_number',
        'tracking_url',
        'expected_delivery_date',
        'changed_by',
        'changed_by_type',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'expected_delivery_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getLabelAttribute(): string
    {
        return Order::statusLabel($this->status);
    }
}
