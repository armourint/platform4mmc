<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalSnapshot extends Model
{
    protected $fillable = [
        'dataset_version_id',
        'system_code',
        'kpi_json',
        'layers_json',
        'hotspots_json',
        'chart_rows_json',
        'checksum',
    ];

    protected $casts = [
        'kpi_json'        => 'array',
        'layers_json'     => 'array',
        'hotspots_json'   => 'array',
        'chart_rows_json' => 'array',
    ];
}
