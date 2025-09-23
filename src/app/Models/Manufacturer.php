<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $fillable = [
        'name',
        'mmc_method',
        'product_category',
        'product_subcategory',
        'address',
        'county_code',
        'county_name',
        'country',
        'website',
        'phone',
        'email',
        'lat',
        'lng',
        'properties',
        'source',
        'is_active',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'is_active' => 'boolean',
        'properties' => 'array',
    ];
}