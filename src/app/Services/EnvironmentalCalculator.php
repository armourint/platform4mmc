<?php

namespace App\Services;

use App\Models\EnvironmentalLayer;
use App\Models\DatasetVersion;
use Illuminate\Support\Collection;

class EnvironmentalCalculator
{
    public function snapshotForSystem(DatasetVersion $dv, string $systemCode): array
    {
        /** @var Collection<int, EnvironmentalLayer> $layers */
        $layers = EnvironmentalLayer::query()
            ->where('dataset_version_id', $dv->id)
            ->where('system_code', $systemCode)
            ->orderBy('id')
            ->get();

        if ($layers->isEmpty()) {
            return [
                'system_code' => $systemCode,
                'layers' => [],
                'totals' => ['a1a3_m2' => 0.0, 'a4_m2' => 0.0],
                'hotspots' => [],
            ];
        }

        $out = [];
        $sumA1A3 = 0.0; $sumA4 = 0.0;

        foreach ($layers as $L) {
            // mass per m² if thickness & density present, else fallback to stored mass
            $mass_m2 = null;
            if ($L->thickness_mm !== null && $L->density_kg_m3 !== null) {
                $mass_m2 = ($L->thickness_mm / 1000.0) * $L->density_kg_m3; // m * kg/m3 => kg/m2
            } elseif ($L->mass_per_m2_kg !== null) {
                $mass_m2 = $L->mass_per_m2_kg;
            }

            $a1a3_m2 = $L->a1a3_per_m2;
            if ($a1a3_m2 === null && $mass_m2 !== null && $L->a1a3_per_kg !== null) {
                $a1a3_m2 = $mass_m2 * $L->a1a3_per_kg;
            }

            $a4_m2 = $L->a4_per_m2 ?? 0.0;

            $sumA1A3 += (float) ($a1a3_m2 ?? 0.0);
            $sumA4   += (float) $a4_m2;

            $out[] = [
                'layer_code'   => $L->layer_code,
                'name'         => $L->layer_name,
                'thickness_mm' => $L->thickness_mm,
                'density_kg_m3'=> $L->density_kg_m3,
                'mass_m2'      => $mass_m2,
                'a1a3_m2'      => $a1a3_m2,
                'a4_m2'        => $a4_m2,
                'source_epd'   => $L->source_epd,
            ];
        }

        // hotspots: top 3 by A1A3 per m²
        $hot = collect($out)
            ->sortByDesc(fn($r) => $r['a1a3_m2'] ?? 0.0)
            ->take(3)
            ->values()
            ->all();

        return [
            'system_code' => $systemCode,
            'layers' => $out,
            'totals' => [
                'a1a3_m2' => $sumA1A3,
                'a4_m2'   => $sumA4,
            ],
            'hotspots' => array_map(fn($r) => [
                'layer_code' => $r['layer_code'],
                'name'       => $r['name'],
                'a1a3_m2'    => $r['a1a3_m2'],
            ], $hot),
        ];
    }
}
