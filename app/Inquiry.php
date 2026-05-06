<?php

namespace App; 

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = [
        'customer_name', 
        'phone_number', 
        'email', 
        'message', 
        'product_id', 
        'status'
    ];
}