<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $casts = [
        'order_amount' => 'float',
        'discount_amount' => 'float',
        'customer_id' => 'integer',
        'shipping_address' => 'integer',
        'shipping_cost' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'billing_address'=> 'integer',
        'extra_discount'=>'float',
        'delivery_man_id'=>'integer',
        'shipping_method_id'=>'integer',
        'seller_id'=>'integer'
    ];

    /**
     * Ordered "happy path" tracking pipeline shown on the customer timeline.
     * Keys are the machine values stored in orders.order_status; values are
     * the human label shown for that step. 'canceled' keeps its legacy
     * one-L spelling so existing rows and cancel/restock logic keep working.
     */
    public const STATUS_FLOW = [
        'pending'            => 'Order Placed',
        'payment_confirmed'  => 'Payment Confirmed',
        'availability_check' => 'Availability Check',
        'confirmed'          => 'Confirmed',
        'processing'         => 'Processing',
        'packed'             => 'Packed',
        'shipped'            => 'Shipped',
        'out_for_delivery'   => 'Out for Delivery',
        'delivered'          => 'Delivered',
    ];

    /** Statuses that end the lifecycle and stop the normal timeline. */
    public const TERMINAL_STATUSES = ['canceled', 'returned', 'refunded', 'failed'];

    /**
     * Single source of truth for label + theme colours for every status.
     * Used by admin dropdown, customer timeline, and order list badges.
     */
    public static function statusMeta(): array
    {
        return [
            'pending'            => ['label' => 'Pending',            'text' => '#92400E', 'bg' => '#FEF3C7'],
            'payment_confirmed'  => ['label' => 'Payment Confirmed',  'text' => '#0B4F8A', 'bg' => '#E0F2FE'],
            'availability_check' => ['label' => 'Availability Check', 'text' => '#92400E', 'bg' => '#FEF3C7'],
            'confirmed'          => ['label' => 'Confirmed',          'text' => '#082A45', 'bg' => '#E2E8F0'],
            'processing'         => ['label' => 'Processing',         'text' => '#0B4F8A', 'bg' => '#DBEAFE'],
            'packed'             => ['label' => 'Packed',             'text' => '#5B21B6', 'bg' => '#EDE9FE'],
            'shipped'            => ['label' => 'Shipped',            'text' => '#0B4F8A', 'bg' => '#DBEAFE'],
            'out_for_delivery'   => ['label' => 'Out for Delivery',   'text' => '#9A3412', 'bg' => '#FFEDD5'],
            'delivered'          => ['label' => 'Delivered',          'text' => '#166534', 'bg' => '#DCFCE7'],
            'canceled'           => ['label' => 'Cancelled',          'text' => '#DC2626', 'bg' => '#FEE2E2'],
            'returned'           => ['label' => 'Returned',           'text' => '#9A3412', 'bg' => '#FFEDD5'],
            'refunded'           => ['label' => 'Refunded',           'text' => '#166534', 'bg' => '#ECFDF5'],
            'failed'             => ['label' => 'Failed',             'text' => '#DC2626', 'bg' => '#FEE2E2'],
        ];
    }

    /** value => label list for the admin status dropdown (excludes legacy 'failed'). */
    public static function selectableStatuses(): array
    {
        $meta = self::statusMeta();
        unset($meta['failed']);
        return array_map(function ($row) {
            return $row['label'];
        }, $meta);
    }

    public static function statusLabel($status): string
    {
        $meta = self::statusMeta();
        return $meta[$status]['label'] ?? ucwords(str_replace('_', ' ', (string) $status));
    }

    public static function statusColors($status): array
    {
        $meta = self::statusMeta();
        return $meta[$status] ?? ['label' => self::statusLabel($status), 'text' => '#64748B', 'bg' => '#F1F5F9'];
    }

    public static function isTerminalStatus($status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }

    /** Position of $status within STATUS_FLOW, or -1 if terminal/unknown. */
    public static function timelineProgress($status): int
    {
        $keys = array_keys(self::STATUS_FLOW);
        $idx = array_search($status, $keys, true);
        return $idx === false ? -1 : $idx;
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class)->orderBy('seller_id', 'ASC');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function sellerName()
    {
        return $this->hasOne(OrderDetail::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shipping()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address');
    }
    public function billingAddress()
    {
        return $this->belongsTo(ShippingAddress::class, 'billing_address');
    }

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class,'delivery_man_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id', 'ASC');
    }
}
