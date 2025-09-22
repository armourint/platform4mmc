<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name','manufacturer','category','standard','declared_unit','meta','published_at',
    ];
    protected $casts = [
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    public function epds() {
        return $this->hasMany(ProductEpdMetric::class);
    }
}
