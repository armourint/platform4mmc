<?php

namespace App\Services\Assessments;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalLayer;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Models\EnvironmentalSnapshot;

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
        // 1) Try to use a precomputed snapshot first (fast path)
        /** @var \App\Models\EnvironmentalSnapshot|null $snap */
        $snap = \App\Models\EnvironmentalSnapshot::query()
            ->where('dataset_version_id', $datasetVersionId)
            ->where('system_code', $systemCode)
            // If multiple snapshots exist (e.g., multiple assemblies), prefer the latest
            // You can change this to a more specific pick once assembly_id is threaded through the UI
            ->orderByDesc('id')
            ->first();

        if ($snap) {
            $kpi      = (array) ($snap->kpi_json ?? []);
            $layersJs = (array) ($snap->layers_json ?? []);
            $hotsJs   = (array) ($snap->hotspots_json ?? []);
            $chartJs  = (array) ($snap->chart_rows_json ?? []);

            // Normalise totals
            $a1a3Total = (float) ($kpi['a1_a3_per_m2'] ?? 0.0);
            $a4Total   = (float) ($kpi['a4_per_m2']   ?? 0.0);
            $massTotal = (float) array_sum(array_map(fn($r) => (float)($r['mass_kg_m2'] ?? 0), $layersJs));
            $cfAvg     = $massTotal > 0 ? $a1a3Total / $massTotal : 0.0;

            // Map layers to your UI shape
            $prettyLayers = array_map(function ($r) {
                return [
                    'layer_no'         => (int)   ($r['layer_no']         ?? 0),
                    'functional_role'  => (string)($r['functional_role']  ?? ''),
                    'generic_material' => (string)($r['generic_material'] ?? ''),
                    'mass_kg_m2'       => (float) ($r['mass_kg_m2']       ?? 0),
                    'carbon_factor'    => (float) ($r['carbon_factor']    ?? 0),
                    'thermal_conductivity_w_mk' => null,
                    'r_value_m2k_w'             => null,
                    'u_value_w_m2k'             => null, // per-layer U is not used; system U is in KPI
                    'a1a3_per_m2'      => (float) ($r['a1a3_per_m2']      ?? 0),
                    'carbon_factor_unit'=> 'kgCO₂e/kg',
                ];
            }, $layersJs);

            // Map hotspots to your UI shape (label + a1a3)
            $hotspots = array_map(function ($r) {
                // snapshots may store 'a1a3_per_m2' or 'a1a3'
                $val = $r['a1a3_per_m2'] ?? $r['a1a3'] ?? 0.0;
                return [
                    'label' => (string)($r['label'] ?? ''),
                    'a1a3'  => (float)$val,
                ];
            }, $hotsJs);

            // Mass series for chart (if you still use it)
            $massSeries = [
                'labels' => array_map(fn($r) => (string)($r['layer_no'] ?? ''), $layersJs),
                'values' => array_map(fn($r) => (float) ($r['mass_kg_m2'] ?? 0), $layersJs),
            ];

            return [
                'system_code' => (string)($kpi['system_code'] ?? $systemCode),
                'system_name' => (string)($kpi['system_name'] ?? $systemCode),
                'kpi' => [
                    'layer_count'              => (int)   count($layersJs),
                    'mass_total_kg_m2'         => (float) $massTotal,
                    'a1a3_total_kgco2e_m2'     => (float) $a1a3Total,
                    'overall_total_kgco2e_m2'  => (float) ($a1a3Total + $a4Total),
                    'cf_avg_kgco2e_per_kg'     => (float) $cfAvg,
                    // You can also surface U here if your UI needs it:
                    // 'u_value_w_m2k' => $kpi['u_value_w_m2k'] ?? null,
                ],
                'layers'     => $prettyLayers,
                'hotspots'   => $hotspots,
                'massSeries' => $massSeries,
            ];
        }

        // 2) Fallback: compute from layers live (slower, but robust)
        $layers = \App\Models\EnvironmentalLayer::query()
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

        // Derive missing a1a3_per_m2 if mass & CF exist
        $layers = $layers->map(function ($r) {
            if (is_null($r->a1a3_per_m2) && $r->mass_kg_m2 && $r->carbon_factor) {
                $r->a1a3_per_m2 = (float)$r->mass_kg_m2 * (float)$r->carbon_factor;
            }
            return $r;
        });

        $massTotal = (float) round($layers->sum('mass_kg_m2'), 6);
        $a1a3Total = (float) round($layers->sum('a1a3_per_m2'), 6);
        $cfAvg     = $massTotal > 0 ? $a1a3Total / $massTotal : 0.0;

        // A4 from factors (per system_code); fallback to constant * mass
        $factor = \App\Models\EnvironmentalFactor::query()
            ->where('dataset_version_id', $datasetVersionId)
            ->where('system_code', $systemCode)
            ->first();
        $a4Total = (float) ($factor?->a4_per_m2 ?? 0.0);

        $prettyLayers = $layers->map(function ($r) {
            return [
                'layer_no'         => (int)   $r->layer_no,
                'functional_role'  => (string)$r->functional_role,
                'generic_material' => (string)$r->generic_material,
                'mass_kg_m2'       => (float) $r->mass_kg_m2,
                'carbon_factor'    => (float) $r->carbon_factor,
                'thermal_conductivity_w_mk' => null,
                'r_value_m2k_w'             => null,
                'u_value_w_m2k'             => null,
                'a1a3_per_m2'      => (float) $r->a1a3_per_m2,
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
                'layer_count'              => (int)   $layers->count(),
                'mass_total_kg_m2'         => (float) $massTotal,
                'a1a3_total_kgco2e_m2'     => (float) $a1a3Total,
                'overall_total_kgco2e_m2'  => (float) ($a1a3Total + $a4Total),
                'cf_avg_kgco2e_per_kg'     => (float) $cfAvg,
            ],
            'layers'     => $prettyLayers,
            'hotspots'   => $hotspots,
            'massSeries' => $massSeries,
        ];
    }

    
}
