<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    protected $casts = [
        'created_at' =>'date:d-M-Y'
    ];

}
