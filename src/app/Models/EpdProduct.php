<?php

// app/Models/EpdProduct.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpdProduct extends Model
{
    protected $fillable = [
        'uuid','country_code','country','category','subcategory','manufacturer','product_name',
        'epd_program','epd_number','reference_year','valid_to',
        'reference_property','reference_unit','category_unit','declared_amount','resulting_amount',
        'mass_per_du_kg','weight_per_m2_kg','thickness_m','length_m','width_m','height_m',
        'area_m2','volume_m3','specific_surface_m2_per_kg','density_kg_m3',
        'thermal_conductivity_w_mk','thermal_resistance_m2k_w','density_kg_litre','coverage_m2_per_litre',
        'a1a3_per_declared_unit','a1a3_sum',
        'gwp_a1','gwp_a2','gwp_a3',
        'gwp_total_a1','gwp_total_a2','gwp_total_a3',
        'gwp_luluc_a1','gwp_luluc_a2','gwp_luluc_a3','gwp_luluc_a1a3_sum',
        'gwp_biogenic_a1','gwp_biogenic_a2','gwp_biogenic_a3','gwp_biogenic_a1a3_sum',
        'gwp_fossil_a1','gwp_fossil_a2','gwp_fossil_a3','gwp_fossil_a1a3_sum',
        'extras',
    ];

    protected $casts = [
        'reference_year' => 'integer',
        'valid_to' => 'date',
        'declared_amount' => 'decimal:6',
        'resulting_amount'=> 'decimal:6',
        'mass_per_du_kg' => 'decimal:6',
        'weight_per_m2_kg' => 'decimal:6',
        'thickness_m' => 'decimal:6',
        'length_m' => 'decimal:6',
        'width_m' => 'decimal:6',
        'height_m' => 'decimal:6',
        'area_m2' => 'decimal:6',
        'volume_m3' => 'decimal:6',
        'specific_surface_m2_per_kg' => 'decimal:6',
        'density_kg_m3' => 'decimal:6',
        'thermal_conductivity_w_mk' => 'decimal:6',
        'thermal_resistance_m2k_w' => 'decimal:6',
        'density_kg_litre' => 'decimal:6',
        'coverage_m2_per_litre' => 'decimal:6',
        'a1a3_per_declared_unit' => 'decimal:6',
        'a1a3_sum' => 'decimal:6',
        'gwp_a1' => 'decimal:6',
        'gwp_a2' => 'decimal:6',
        'gwp_a3' => 'decimal:6',
        'gwp_total_a1' => 'decimal:6',
        'gwp_total_a2' => 'decimal:6',
        'gwp_total_a3' => 'decimal:6',
        'gwp_luluc_a1' => 'decimal:6',
        'gwp_luluc_a2' => 'decimal:6',
        'gwp_luluc_a3' => 'decimal:6',
        'gwp_luluc_a1a3_sum' => 'decimal:6',
        'gwp_biogenic_a1' => 'decimal:6',
        'gwp_biogenic_a2' => 'decimal:6',
        'gwp_biogenic_a3' => 'decimal:6',
        'gwp_biogenic_a1a3_sum' => 'decimal:6',
        'gwp_fossil_a1' => 'decimal:6',
        'gwp_fossil_a2' => 'decimal:6',
        'gwp_fossil_a3' => 'decimal:6',
        'gwp_fossil_a1a3_sum' => 'decimal:6',
        'extras' => 'array',
    ];
}