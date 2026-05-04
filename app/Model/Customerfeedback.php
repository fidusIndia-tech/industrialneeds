<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Customerfeedback extends Model
{
    protected $casts = [
        'seen'       => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected $table = 'customer_feedback';

    protected $fillable = [
        'name',
        'full_name',
        'company_name	',
        'phone_no',
        'feedback',
    ];
}
