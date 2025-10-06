<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalProperty extends Model
{
    protected $fillable = [
        'environmental_system_id',
        'u_value_w_m2k',
        'r_value_m2k_w',
        'lambda_w_mk',
        'fire_class',
        'acoustic_db',
        'life_expectancy_years',
        'notes_json',
    ];

    protected $casts = [
        'u_value_w_m2k' => 'float',
        'r_value_m2k_w' => 'float',
        'lambda_w_mk'   => 'float',
        'acoustic_db'   => 'integer',
        'life_expectancy_years' => 'integer',
        'notes_json'    => 'array',
    ];
}
