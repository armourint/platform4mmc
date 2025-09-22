<?php

namespace App\Support;

final class ExcelHeader
{
    /**
     * Normalize a raw header: lowercase, trim, collapse whitespace,
     * swap Unicode dashes to hyphen, strip accents.
     */
    public static function norm(string $h): string
    {
        $h = trim(mb_strtolower($h));
        $h = preg_replace('/[\x{2010}-\x{2015}]/u','-', $h); // any unicode dash -> hyphen
        $h = preg_replace('/\s+/',' ', $h);
        return $h;
    }

    /**
     * Map a normalized header to our canonical field names.
     */
    public static function map(string $h): ?string
    {
        $h = self::norm($h);

        $map = [
            // id / taxonomy
            'system category'            => 'system_category',
            'system id'                  => 'assembly_id',
            'mmc method'                 => 'mmc_method',   // <— NEW
            'mmc method (code)'          => 'system_code',  // if a variant appears
            'system name'                => 'system_name',
            'source header'              => 'source_header',
            'layer no.'                  => 'layer_no',
            'functional role'            => 'functional_role',
            'generic material'           => 'generic_material',

            // geometry / quantities
            'length (m)'                 => 'length_m',
            'height (m)'                 => 'height_m',
            'thickness (m)'              => 'thickness_m',
            'element volume (m3)'        => 'element_volume_m3',
            'element number'             => 'element_number',
            'total volume (m3)'          => 'total_volume_m3',
            'density (kg.m3)'            => 'density_kg_m3',
            'mass (kg/m²)'               => 'mass_kg_m2',
            'mass (kg/m2)'               => 'mass_kg_m2',

            // carbon fields
            'carbon factor'                              => 'carbon_factor',
            'a1–a3 (kgco₂e / 5.76 m²)'                   => 'a1a3_per_5_76_m2',
            'a1-a3 (kgco₂e / 5.76 m²)'                   => 'a1a3_per_5_76_m2',
            'a1–a3 (kgco₂e/5.76 m²)'                     => 'a1a3_per_5_76_m2',
            'a1-a3 (kgco₂e/5.76 m²)'                     => 'a1a3_per_5_76_m2',
            'a1–a3 (kgco₂e/m²)'                          => 'a1a3_per_m2',
            'a1-a3 (kgco₂e/m²)'                          => 'a1a3_per_m2',
            'a1-a3 (kgco2e/m2)'                          => 'a1a3_per_m2',
        ];

        return $map[$h] ?? null;
    }
}
