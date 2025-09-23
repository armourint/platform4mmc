<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpdProduct extends Model
{
    protected $table = 'epd_products';
    protected $guarded = []; // importer controls inputs

    public function metrics()
    {
        return $this->hasMany(ProductEpdMetric::class, 'product_id');
    }
}
