<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalLayer extends Model
{
    protected $fillable = [
        // dataset + system identity
        'dataset_version_id',
        'system_code',
        'mmc_method',
        'assembly_id',
        'system_name',
        'system_category',
        'source_header',

        // layer identity
        'layer_no',
        'functional_role',
        'generic_material',

        // geometry / quantities
        'length_m',
        'height_m',
        'thickness_m',
        'element_volume_m3',
        'element_number',
        'total_volume_m3',

        // physical props
        'density_kg_m3',
        'mass_kg_m2',

        // carbon
        'carbon_factor',
        'carbon_factor_unit',          // NEW (e.g., kgCO2e/kg)
        'a1a3_per_5_76_m2',
        'a1a3_per_m2',

        // thermal (per-layer; optional)
        'thermal_conductivity_w_mk',   // λ
        'r_value_m2k_w',               // R
        'u_value_w_m2k',               // rarely per-layer; can be null

        // durability / lifespan
        'life_expectancy_years',       // NEW
    ];

    protected $casts = [
        // geometry
        'length_m'          => 'float',
        'height_m'          => 'float',
        'thickness_m'       => 'float',
        'element_volume_m3' => 'float',
        'element_number'    => 'int',
        'total_volume_m3'   => 'float',

        // physical
        'density_kg_m3'     => 'float',
        'mass_kg_m2'        => 'float',

        // carbon
        'carbon_factor'     => 'float',
        'a1a3_per_5_76_m2'  => 'float',
        'a1a3_per_m2'       => 'float',

        // thermal
        'thermal_conductivity_w_mk' => 'float',
        'r_value_m2k_w'             => 'float',
        'u_value_w_m2k'             => 'float',

        // durability
        'life_expectancy_years'     => 'float',

        // misc
        'layer_no'          => 'int',
    ];

    public function datasetVersion()
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
