<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Models\EnvironmentalLayer;
use Illuminate\Support\Arr;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Results extends Component
{
    public Assessment $assessment;

    // Shared to blade
    public array $summary = [];
    public array $included = [];
    public array $excluded = [];

    // Environmental
    public ?int $datasetVersionId = null;
    public ?string $systemCode = null;

    public array $envTotals = [
        'a1_a3' => 0.0,        // kgCO2e/m²
        'a4_a5' => 0.0,
        'c1_c4' => 0.0,
        'sum'   => 0.0,
    ];

    // detail
    public array $envLayers = [];     // per-layer rows for table
    public array $hotspots  = [];     // [{label, a1a3}]
    public array $kpi       = [       // headline KPIs for the card row
        'layer_count'            => 0,
        'mass_total_kg_m2'       => 0.0,
        'a1a3_total_kgco2e_m2'   => 0.0,
        'overall_total_kgco2e_m2'=> 0.0,
    ];

    // chart series for per-layer mass
    public array $massSeries = [
        'labels' => [],
        'values' => [],
    ];

    public function mount(Assessment $assessment): void
    {
        $this->assessment = $assessment;

        if ($assessment->type === 'viability') {
            $this->prepareViability();
            return;
        }

        if ($assessment->type === 'environmental') {
            $this->prepareEnvironmental();
            return;
        }
    }

    protected function prepareViability(): void
    {
        $out = $this->assessment->outputs ?? [];

        // Some older runs stored included/excluded in different shapes; normalise both cases.
        $this->summary = Arr::get($out, 'summary', []);

        $systems = Arr::get($out, 'systems');
        if (is_array($systems)) {
            $included = [];
            $excluded = [];
            foreach ($systems as $code => $data) {
                $name = $code;
                $status = $data['status'] ?? 'included';
                $reasons = $data['reasons'] ?? [];
                $target = ($status === 'included') ? $included : $excluded;
                $target[] = [
                    'code' => $code,
                    'name' => $name,
                    'reasons' => $reasons,
                ];
                if ($status === 'included') $included = $target; else $excluded = $target;
            }
            $this->included = collect($included)->values()->all();
            $this->excluded = collect($excluded)->values()->all();
        } else {
            // legacy keys
            $this->included = collect(Arr::get($out, 'included', []))->values()->all();
            $this->excluded = collect(Arr::get($out, 'excluded', []))->values()->all();
        }
    }

    protected function prepareEnvironmental(): void
    {
        $inputs = $this->assessment->inputs ?? [];
        $outputs = $this->assessment->outputs ?? [];

        $this->datasetVersionId = $this->assessment->dataset_version_id ?: null;
        $this->systemCode       = Arr::get($inputs, 'selected_system_code');

        // Prefer a ready-made snapshot if your EnvironmentalCalculator saved one
        $snapshot = Arr::get($outputs, 'layer_snapshot');
        if (is_array($snapshot) && !empty($snapshot)) {
            $this->absorbSnapshot($snapshot);

            $this->summary = [
                'status' => 'OK',
                'note'   => 'Layer dataset used',
            ];
            return;
        }

        // Otherwise build from DB rows for the current dataset_version + system_code
        $layers = collect();
        if ($this->datasetVersionId && $this->systemCode) {
            $layers = EnvironmentalLayer::query()
                ->where('dataset_version_id', $this->datasetVersionId)
                ->where('system_code', $this->systemCode)
                ->orderBy('layer_no')
                ->get();
        }

        if ($layers->isNotEmpty()) {
            $a1a3 = (float) round($layers->sum('a1a3_per_m2'), 6);
            $a4a5 = 0.0;
            $c1c4 = 0.0;

            $this->envTotals = [
                'a1_a3' => $a1a3,
                'a4_a5' => $a4a5,
                'c1_c4' => $c1c4,
                'sum'   => $a1a3 + $a4a5 + $c1c4,
            ];

            $this->envLayers = $layers->map(function (EnvironmentalLayer $r) {
                return [
                    'layer_no'         => $r->layer_no,
                    'functional_role'  => $r->functional_role,
                    'generic_material' => $r->generic_material,
                    'mass_kg_m2'       => $r->mass_kg_m2,
                    'a1a3_per_m2'      => $r->a1a3_per_m2,
                    'carbon_factor'    => $r->carbon_factor,
                    'carbon_factor_unit' => $r->carbon_factor_unit,
                    'thermal_conductivity_w_mk' => $r->thermal_conductivity_w_mk,
                    'r_value_m2k_w'     => $r->r_value_m2k_w,
                    'u_value_w_m2k'     => $r->u_value_w_m2k,
                    'life_expectancy_years' => $r->life_expectancy_years,
                ];
            })->values()->all();

            // KPIs
            $this->kpi = [
                'layer_count'              => count($this->envLayers),
                'mass_total_kg_m2'         => (float) round($layers->sum('mass_kg_m2'), 6),
                'a1a3_total_kgco2e_m2'     => $a1a3,
                'overall_total_kgco2e_m2'  => $a1a3, // add A4/C later if you store them per m²
            ];

            // Hotspots: top 5 by A1–A3 contribution
            $this->hotspots = $layers
                ->map(fn ($r) => [
                    'label' => $r->generic_material ?: ($r->functional_role ?: 'Layer '.$r->layer_no),
                    'a1a3'  => (float) $r->a1a3_per_m2,
                ])
                ->sortByDesc('a1a3')
                ->take(5)
                ->values()
                ->all();

            // Chart series
            $this->massSeries = [
                'labels' => collect($this->envLayers)->map(fn($r) => $r['generic_material'] ?? $r['functional_role'] ?? 'Layer')->all(),
                'values' => collect($this->envLayers)->map(fn($r) => (float) ($r['mass_kg_m2'] ?? 0))->all(),
            ];

            $this->summary = [
                'status' => 'OK',
                'note'   => 'Layer dataset used',
            ];
            return;
        }

        // Fallback to typed totals saved with the assessment
        $a1a3 = (float) (Arr::get($inputs, 'a1_a3') ?: 0);
        $a4a5 = (float) (Arr::get($inputs, 'a4_a5') ?: 0);
        $c1c4 = (float) (Arr::get($inputs, 'c1_c4') ?: 0);

        $this->envTotals = [
            'a1_a3' => $a1a3,
            'a4_a5' => $a4a5,
            'c1_c4' => $c1c4,
            'sum'   => $a1a3 + $a4a5 + $c1c4,
        ];
        $this->envLayers = [];
        $this->hotspots = [];
        $this->kpi = [
            'layer_count'              => 0,
            'mass_total_kg_m2'         => 0,
            'a1a3_total_kgco2e_m2'     => $a1a3,
            'overall_total_kgco2e_m2'  => $a1a3 + $a4a5 + $c1c4,
        ];
        $this->massSeries = ['labels' => [], 'values' => []];

        $this->summary = [
            'status' => 'OK',
            'note'   => 'No layer dataset linked. Totals shown from inputs.',
        ];
    }

    protected function absorbSnapshot(array $snapshot): void
    {
        $kpi = Arr::get($snapshot, 'kpi', []);
        $layers = Arr::get($snapshot, 'layers', []);
        $hotspots = Arr::get($snapshot, 'hotspots', []);

        $this->kpi = [
            'layer_count'              => (int) ($kpi['layer_count'] ?? count($layers)),
            'mass_total_kg_m2'         => (float) ($kpi['mass_total_kg_m2'] ?? collect($layers)->sum('mass_kg_m2')),
            'a1a3_total_kgco2e_m2'     => (float) ($kpi['a1a3_total_kgco2e_m2'] ?? collect($layers)->sum('a1a3_per_m2')),
            'overall_total_kgco2e_m2'  => (float) ($kpi['overall_total_kgco2e_m2'] ?? collect($layers)->sum('a1a3_per_m2')),
        ];

        $this->envTotals = [
            'a1_a3' => $this->kpi['a1a3_total_kgco2e_m2'],
            'a4_a5' => 0.0,
            'c1_c4' => 0.0,
            'sum'   => $this->kpi['overall_total_kgco2e_m2'],
        ];

        // normalise layers list (keep extra fields if present)
        $this->envLayers = collect($layers)->map(function ($r, $idx) {
            return [
                'layer_no'         => $r['layer_no'] ?? ($idx + 1),
                'functional_role'  => $r['functional_role'] ?? null,
                'generic_material' => $r['generic_material'] ?? null,
                'mass_kg_m2'       => isset($r['mass_kg_m2']) ? (float)$r['mass_kg_m2'] : null,
                'a1a3_per_m2'      => isset($r['a1a3_per_m2']) ? (float)$r['a1a3_per_m2'] : null,
                'carbon_factor'    => $r['carbon_factor'] ?? null,
                'carbon_factor_unit' => $r['carbon_factor_unit'] ?? null,
                'thermal_conductivity_w_mk' => $r['thermal_conductivity_w_mk'] ?? null,
                'r_value_m2k_w'     => $r['r_value_m2k_w'] ?? null,
                'u_value_w_m2k'     => $r['u_value_w_m2k'] ?? null,
                'life_expectancy_years' => $r['life_expectancy_years'] ?? null,
            ];
        })->values()->all();

        $this->hotspots = collect($hotspots)->map(function ($h) {
            return [
                'label' => (string) ($h['label'] ?? ''),
                'a1a3'  => (float) ($h['a1a3'] ?? 0),
            ];
        })->all();

        $this->massSeries = [
            'labels' => collect($this->envLayers)->map(fn($r) => $r['generic_material'] ?? $r['functional_role'] ?? 'Layer')->all(),
            'values' => collect($this->envLayers)->map(fn($r) => (float) ($r['mass_kg_m2'] ?? 0))->all(),
        ];
    }

    public function render()
    {
        return view('livewire.assessments.results');
    }
}
