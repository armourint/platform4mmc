<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class County extends Model
{
    protected $fillable = [
        'code', 'name', 'source', 'geometry', 'centroid_lat', 'centroid_lng',
    ];

    protected $casts = [
        'geometry' => 'array',
        'centroid_lat' => 'float',
        'centroid_lng' => 'float',
    ];
}
