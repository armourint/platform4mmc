<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductEpdMetric extends Model
{
    protected $guarded = [];
    public function product()
    {
        return $this->belongsTo(EpdProduct::class, 'product_id');
    }
}
