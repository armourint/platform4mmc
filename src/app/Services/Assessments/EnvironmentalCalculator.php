<?php

namespace App\Services\Assessments;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalFactor;
use App\Models\EnvironmentalLayer;
use Illuminate\Support\Collection;

class EnvironmentalCalculator
{
    /**
     * Return distinct systems (code + name) for a given category in a dataset.
     * Example category: "Cladding Systems"
     */
    public function systemsForCategory(DatasetVersion $dv, ?string $category): array
    {
        if (!$dv || !$category) {
            return [];
        }

        return EnvironmentalLayer::query()
            ->where('dataset_version_id', $dv->id)
            ->where('system_category', $category)
            ->select('system_code', 'system_name')
            ->groupBy('system_code', 'system_name')
            ->orderBy('system_name')
            ->get()
            ->map(fn ($r) => [
                'code' => $r->system_code,
                'name' => $r->system_name ?? $r->system_code,
            ])
            ->all();
    }

    /**
     * Build a per-m² snapshot for the given dataset+system:
     *  - per-layer rows (mass_kg_m2, a1a3_per_m2, etc.)
     *  - KPI totals (layer_count, mass_total, a1a3_total, a4_total, overall_total)
     *  - top 5 hotspots by A1–A3
     */
    public function snapshotForSystem(DatasetVersion $dv, ?string $systemCode): ?array
    {
        if (!$dv || !$systemCode) {
            return null;
        }

        /** @var Collection<int, EnvironmentalLayer> $layers */
        $layers = EnvironmentalLayer::query()
            ->where('dataset_version_id', $dv->id)
            ->where('system_code', $systemCode)
            ->orderBy('layer_no')
            ->get();

        if ($layers->isEmpty()) {
            return [
                'system_code' => $systemCode,
                'system_name' => null,
                'layers'      => [],
                'kpi'         => [
                    'layer_count'              => 0,
                    'mass_total_kg_m2'         => 0.0,
                    'a1a3_total_kgco2e_m2'     => 0.0,
                    'a4_total_kgco2e_m2'       => 0.0,
                    'overall_total_kgco2e_m2'  => 0.0,
                ],
                'hotspots'    => [],
            ];
        }

        $systemName = $layers->first()->system_name;

        // Optional A4 per-m² at system level (from environmental_factors)
        $a4_per_m2 = 0.0;
        $factor = EnvironmentalFactor::query()
            ->where('dataset_version_id', $dv->id)
            ->where('system_code', $systemCode)
            ->first();
        if ($factor && $factor->a4_per_m2 !== null) {
            $a4_per_m2 = (float) $factor->a4_per_m2;
        }

        $rows = [];
        $sumMass = 0.0;
        $sumA1A3 = 0.0;

        foreach ($layers as $L) {
            // mass per m²: prefer stored, else thickness_m * density_kg_m3
            $mass = $L->mass_kg_m2;
            if ($mass === null && $L->thickness_m !== null && $L->density_kg_m3 !== null) {
                $mass = (float) $L->thickness_m * (float) $L->density_kg_m3;
            }
            $mass = (float) ($mass ?? 0.0);

            // A1–A3 per m²: prefer imported field; else compute via mass * carbon_factor
            $a1a3 = $L->a1a3_per_m2;
            if ($a1a3 === null && $mass !== null && $L->carbon_factor !== null) {
                $a1a3 = $mass * (float) $L->carbon_factor;
            }
            $a1a3 = (float) ($a1a3 ?? 0.0);

            $sumMass += $mass;
            $sumA1A3 += $a1a3;

            $rows[] = [
                'layer_no'         => (int) $L->layer_no,
                'functional_role'  => $L->functional_role,
                'generic_material' => $L->generic_material,
                'mass_kg_m2'       => $mass,
                'a1a3_per_m2'      => $a1a3,
                'carbon_factor'    => $L->carbon_factor !== null ? (float) $L->carbon_factor : null,
                'source_header'    => $L->source_header,
            ];
        }

        $hotspots = collect($rows)
            ->sortByDesc('a1a3_per_m2')
            ->take(5)
            ->map(fn ($r) => [
                'label' => trim(($r['generic_material'] ?: $r['functional_role'] ?: ('Layer '.$r['layer_no']))),
                'a1a3'  => $r['a1a3_per_m2'],
            ])
            ->values()
            ->all();

        return [
            'system_code' => $systemCode,
            'system_name' => $systemName,
            'layers'      => $rows,
            'kpi'         => [
                'layer_count'              => count($rows),
                'mass_total_kg_m2'         => round($sumMass, 2),
                'a1a3_total_kgco2e_m2'     => round($sumA1A3, 2),
                'a4_total_kgco2e_m2'       => round($a4_per_m2, 2),
                'overall_total_kgco2e_m2'  => round($sumA1A3 + $a4_per_m2, 2),
            ],
            'hotspots'    => $hotspots,
        ];
    }
}
