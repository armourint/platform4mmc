<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalLayer extends Model
{
    protected $fillable = [
        'dataset_version_id',
        'system_code',
        'layer_code',
        'layer_name',
        'thickness_mm',
        'density_kg_m3',
        'a1a3_per_kg',
        'a1a3_per_m2',
        'a4_per_m2',
        'source_epd',
        'notes',
        // Optional mapping helpers from your workbook:
        'system_id_ref',
        'system_name_ref',
        'mass_per_m2_kg',
        'carbon_factor_kgco2e_per_kg',
    ];

    protected $casts = [
        'thickness_mm' => 'float',
        'density_kg_m3' => 'float',
        'a1a3_per_kg' => 'float',
        'a1a3_per_m2' => 'float',
        'a4_per_m2' => 'float',
        'mass_per_m2_kg' => 'float',
        'carbon_factor_kgco2e_per_kg' => 'float',
    ];
}
