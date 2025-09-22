<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalFactor extends Model
{
    protected $fillable = [
        'dataset_version_id','system_code','a1_a3_per_m2','a4_per_m2','meta'
    ];
    protected $casts = [
        'meta' => 'array',
        'a1_a3_per_m2' => 'decimal:6',
        'a4_per_m2' => 'decimal:6',
    ];

    public function datasetVersion() {
        return $this->belongsTo(DatasetVersion::class);
    }
}
