<?php

namespace App\Services\Assessments;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalLayer;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EnvironmentalCalculator
{
    /** Return latest published dataset_version_id for module=environmental. */
    public function latestPublishedDatasetId(): ?int
    {
        $dv = DatasetVersion::query()
            ->where('module', 'environmental')
            ->where('status', 'published')
            ->orderByDesc('id')
            ->first();

        return $dv?->id;
    }

    /** Categories we support, label ↔ key for UI. */
    public function categories(): array
    {
        // DB stores in environmental_layers.system_category as: Wall | Cladding | Slab
        return [
            'wall'     => ['label' => 'Wall Systems',     'db' => 'Wall'],
            'cladding' => ['label' => 'Cladding Systems', 'db' => 'Cladding'],
            'slab'     => ['label' => 'Slab Systems',     'db' => 'Slab'],
        ];
    }

    /** MMC category list for a given dataset + system_category (distinct mmc_method). */
    public function mmcList(int $datasetVersionId, string $categoryKey): array
    {
        $cat = $this->categories()[$categoryKey]['db'] ?? null;
        if (!$cat) return [];

        return EnvironmentalLayer::query()
            ->where('dataset_version_id', $datasetVersionId)
            ->where('system_category', $cat)
            ->whereNotNull('mmc_method')
            ->select('mmc_method')
            ->groupBy('mmc_method')
            ->orderBy('mmc_method')
            ->pluck('mmc_method')
            ->all();
    }

    /**
     * Systems list for a given dataset + category + (optional) mmc filter.
     * Returns: [ ['code' => 'BLOCK', 'name' => 'Standard Masonry', 'assembly_id' => '...'], ... ]
     */
    public function systemsFor(int $datasetVersionId, string $categoryKey, ?string $mmc = null): array
    {
        $cat = $this->categories()[$categoryKey]['db'] ?? null;
        if (!$cat) return [];

        $q = EnvironmentalLayer::query()
            ->where('dataset_version_id', $datasetVersionId)
            ->where('system_category', $cat);

        if ($mmc) $q->where('mmc_method', $mmc);

        // Distinct by system_code; pull a representative system_name/assembly_id
        $rows = $q->select('system_code', 'system_name', 'assembly_id')
            ->groupBy('system_code', 'system_name', 'assembly_id')
            ->orderBy('system_code')
            ->get();

        return $rows->map(fn($r) => [
            'code'        => (string) $r->system_code,
            'name'        => (string) ($r->system_name ?: $r->system_code),
            'assembly_id' => $r->assembly_id ? (string) $r->assembly_id : null,
        ])->values()->all();
    }

    /**
     * Snapshot for a single system (all its layers), computing KPIs + series for charts.
     * Structure mirrors what we already use on Results.
     */
    public function snapshotForSystem(int $datasetVersionId, string $systemCode): array
    {
        $layers = EnvironmentalLayer::query()
            ->where('dataset_version_id', $datasetVersionId)
            ->where('system_code', $systemCode)
            ->orderBy('layer_no')
            ->get();

        if ($layers->isEmpty()) {
            return [
                'system_code' => $systemCode,
                'system_name' => null,
                'kpi' => [
                    'layer_count'            => 0,
                    'mass_total_kg_m2'       => 0.0,
                    'a1a3_total_kgco2e_m2'   => 0.0,
                    'overall_total_kgco2e_m2'=> 0.0,
                    'cf_avg_kgco2e_per_kg'   => 0.0,
                ],
                'layers'     => [],
                'hotspots'   => [],
                'massSeries' => ['labels' => [], 'values' => []],
            ];
        }

        $massTotal = (float) round($layers->sum('mass_kg_m2'), 6);
        $a1a3Total = (float) round($layers->sum('a1a3_per_m2'), 6);
        $cfAvg     = $massTotal > 0 ? $a1a3Total / $massTotal : 0.0;

        $prettyLayers = $layers->map(function (EnvironmentalLayer $r) {
            return [
                'layer_no'         => $r->layer_no,
                'functional_role'  => $r->functional_role,
                'generic_material' => $r->generic_material,
                'mass_kg_m2'       => $r->mass_kg_m2,
                'carbon_factor'    => $r->carbon_factor,
                'thermal_conductivity_w_mk' => null, // fill when you have λ
                'r_value_m2k_w'             => null, // fill when you have R
                'u_value_w_m2k'             => null, // fill when you have U
                'a1a3_per_m2'      => $r->a1a3_per_m2,
                'carbon_factor_unit'=> 'kgCO₂e/kg',
            ];
        })->values()->all();

        $hotspots = $layers
            ->map(fn($r) => [
                'label' => $r->generic_material ?: ($r->functional_role ?: 'Layer '.$r->layer_no),
                'a1a3'  => (float) $r->a1a3_per_m2,
            ])
            ->sortByDesc('a1a3')
            ->take(5)
            ->values()
            ->all();

        $massSeries = [
            'labels' => $layers->pluck('layer_no')->map(fn($n) => (string)$n)->values()->all(),
            'values' => $layers->pluck('mass_kg_m2')->map(fn($v) => (float)$v)->values()->all(),
        ];

        return [
            'system_code' => $systemCode,
            'system_name' => (string)($layers->first()->system_name ?? $systemCode),
            'kpi' => [
                'layer_count'              => (int) $layers->count(),
                'mass_total_kg_m2'         => $massTotal,
                'a1a3_total_kgco2e_m2'     => $a1a3Total,
                'overall_total_kgco2e_m2'  => $a1a3Total, // extend when you add A4
                'cf_avg_kgco2e_per_kg'     => $cfAvg,
            ],
            'layers'     => $prettyLayers,
            'hotspots'   => $hotspots,
            'massSeries' => $massSeries,
        ];
    }
}
