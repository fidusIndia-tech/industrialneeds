<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCostByCountry extends Model
{
    use HasFactory;

    protected $table = 'shipping_cost_by_country';

    protected $fillable = ['country_id','shipping_cost','product_id','duration'];

    public function country(){
        return $this->belongsTo(Country::class,'country_id');
    }
}
