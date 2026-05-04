<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CartShipping extends Model
{
    protected $table = 'cart_shippings';
    
     protected $fillable = ['cart_group_id','shipping_method_id','shipping_cost'];
}
