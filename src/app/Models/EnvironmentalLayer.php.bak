<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalLayer extends Model
{
    protected $fillable = [
        'dataset_version_id',
        'system_code',
        'mmc_method',
        'assembly_id',
        'system_name',
        'system_category',
        'source_header',
        'layer_no',
        'functional_role',
        'generic_material',
        'length_m',
        'height_m',
        'thickness_m',
        'element_volume_m3',
        'element_number',
        'total_volume_m3',
        'density_kg_m3',
        'mass_kg_m2',
        'carbon_factor',
        'a1a3_per_5_76_m2',
        'a1a3_per_m2',
    ];

    protected $casts = [
        'length_m'            => 'float',
        'height_m'            => 'float',
        'thickness_m'         => 'float',
        'element_volume_m3'   => 'float',
        'element_number'      => 'int',
        'total_volume_m3'     => 'float',
        'density_kg_m3'       => 'float',
        'mass_kg_m2'          => 'float',
        'carbon_factor'       => 'float',
        'a1a3_per_5_76_m2'    => 'float',
        'a1a3_per_m2'         => 'float',
        'layer_no'            => 'int',
    ];

    public function datasetVersion()
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
