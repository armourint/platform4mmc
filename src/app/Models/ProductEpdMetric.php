<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductEpdMetric extends Model
{
    protected $fillable = [
        'product_id','country','program','reference','declared_unit',
        'a1_a3_per_unit','a4_per_unit','density','meta'
    ];
    protected $casts = [
        'meta' => 'array',
        'a1_a3_per_unit' => 'decimal:6',
        'a4_per_unit' => 'decimal:6',
        'density' => 'decimal:6',
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
