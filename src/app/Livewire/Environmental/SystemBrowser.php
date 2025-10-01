<?php

namespace App\Livewire\Environmental;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalLayer;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SystemBrowser extends Component
{
    public ?int $datasetVersionId = null;

    /** Filters */
    public ?string $selectedCategory = null;

    /** Option selections */
    public ?string $codeA = null;
    public ?string $codeB = null;

    /** Data for UI */
    public array $categories = [];       // ['Cladding Systems', 'Wall Systems', ...]
    public array $systemsByCategory = []; // ['Cladding Systems' => [['code'=>'BLOCK','name'=>'...'], ...]]
    public array $optionA = [];          // computed pack for A
    public array $optionB = [];          // computed pack for B

    public function mount(): void
    {
        // latest published dataset for environmental module
        $dv = DatasetVersion::query()
            ->where('module', 'environmental')
            ->where('status', 'published')
            ->latest('id')
            ->first();

        $this->datasetVersionId = $dv?->id;

        // Load categories and systems
        $this->loadTaxonomy();

        // Defaults: first category + first two systems if available
        if (!$this->selectedCategory && !empty($this->categories)) {
            $this->selectedCategory = $this->categories[0];
        }
        $this->pickDefaultSystems();

        // Build both options
        $this->optionA = $this->buildOptionPack($this->codeA);
        $this->optionB = $this->buildOptionPack($this->codeB);
    }

    public function updatedSelectedCategory(): void
    {
        $this->pickDefaultSystems();
        $this->optionA = $this->buildOptionPack($this->codeA);
        $this->optionB = $this->buildOptionPack($this->codeB);
    }

    public function updatedCodeA(): void
    {
        $this->optionA = $this->buildOptionPack($this->codeA);
    }

    public function updatedCodeB(): void
    {
        $this->optionB = $this->buildOptionPack($this->codeB);
    }

    protected function loadTaxonomy(): void
    {
        if (!$this->datasetVersionId) {
            $this->categories = [];
            $this->systemsByCategory = [];
            return;
        }

        $cats = EnvironmentalLayer::query()
            ->where('dataset_version_id', $this->datasetVersionId)
            ->select('system_category')
            ->groupBy('system_category')
            ->orderBy('system_category')
            ->pluck('system_category')
            ->filter()
            ->values()
            ->all();

        $this->categories = $cats;

        $map = [];
        foreach ($cats as $cat) {
            $rows = EnvironmentalLayer::query()
                ->where('dataset_version_id', $this->datasetVersionId)
                ->where('system_category', $cat)
                ->select('system_code', 'system_name')
                ->groupBy('system_code', 'system_name')
                ->orderBy('system_name')
                ->get()
                ->map(fn($r) => [
                    'code' => $r->system_code,
                    'name' => $r->system_name ?: $r->system_code,
                ])->values()->all();

            $map[$cat] = $rows;
        }

        $this->systemsByCategory = $map;
    }

    protected function pickDefaultSystems(): void
    {
        $this->codeA = null;
        $this->codeB = null;

        $list = $this->systemsByCategory[$this->selectedCategory] ?? [];
        if (!empty($list)) {
            $this->codeA = $list[0]['code'] ?? null;
            $this->codeB = $list[1]['code'] ?? ($list[0]['code'] ?? null);
        }
    }

    protected function buildOptionPack(?string $systemCode): array
    {
        if (!$systemCode || !$this->datasetVersionId) {
            return [
                'system_code' => $systemCode,
                'system_name' => null,
                'kpi'         => [],
                'layers'      => [],
                'chartRows'   => [],
            ];
        }

        $layers = EnvironmentalLayer::query()
            ->where('dataset_version_id', $this->datasetVersionId)
            ->where('system_category', $this->selectedCategory)
            ->where('system_code', $systemCode)
            ->orderBy('layer_no')
            ->get();

        if ($layers->isEmpty()) {
            return [
                'system_code' => $systemCode,
                'system_name' => null,
                'kpi'         => [],
                'layers'      => [],
                'chartRows'   => [],
            ];
        }

        $name = $layers->first()->system_name ?: $systemCode;

        // KPI totals
        $layerCount   = $layers->count();
        $massTotal    = (float) $layers->sum('mass_kg_m2');
        $a1a3Total    = (float) $layers->sum('a1a3_per_m2');

        // Thermal aggregation (R = sum of per-layer R, if available; U = 1/R)
        $sumR = (float) $layers->sum(function ($r) {
            if (is_numeric($r->r_value_m2k_w) && $r->r_value_m2k_w > 0) {
                return (float) $r->r_value_m2k_w;
            }
            // If thickness and conductivity are present, compute R
            if (is_numeric($r->thickness_m) && $r->thickness_m > 0 && is_numeric($r->thermal_conductivity_w_mk) && $r->thermal_conductivity_w_mk > 0) {
                return (float) ($r->thickness_m / $r->thermal_conductivity_w_mk);
            }
            return 0;
        });
        $uValue = $sumR > 0 ? (1.0 / $sumR) : null;

        // System life expectancy: conservative = min of layer life spans
        $life = $layers->pluck('life_expectancy_years')
            ->filter(fn($v) => is_numeric($v) && $v > 0)
            ->min();
        $life = $life ? (float) $life : null;

        // Rows for table
        $rows = $layers->map(function (EnvironmentalLayer $r) {
            return [
                'layer_no'                 => $r->layer_no,
                'functional_role'          => $r->functional_role,
                'generic_material'         => $r->generic_material,
                'mass_kg_m2'               => $r->mass_kg_m2,
                'carbon_factor'            => $r->carbon_factor,
                'carbon_factor_unit'       => $r->carbon_factor_unit,
                'a1a3_per_m2'              => $r->a1a3_per_m2,
                'thickness_m'              => $r->thickness_m,
                'thermal_conductivity_w_mk'=> $r->thermal_conductivity_w_mk,
                'r_value_m2k_w'            => $r->r_value_m2k_w ?: (
                    (is_numeric($r->thickness_m) && is_numeric($r->thermal_conductivity_w_mk) && $r->thermal_conductivity_w_mk > 0)
                        ? $r->thickness_m / $r->thermal_conductivity_w_mk
                        : null
                ),
                'u_value_w_m2k'            => $r->u_value_w_m2k,
                'life_expectancy_years'    => $r->life_expectancy_years,
            ];
        })->values()->all();

        // Chart rows
        $chartRows = $layers->map(function (EnvironmentalLayer $r) {
            $label = $r->generic_material ?: ($r->functional_role ?: 'Layer '.$r->layer_no);
            return [
                'label' => (string) $label,
                'mass'  => (float) ($r->mass_kg_m2 ?? 0),
                'a1a3'  => (float) ($r->a1a3_per_m2 ?? 0),
            ];
        })->values()->all();

        return [
            'system_code' => $systemCode,
            'system_name' => $name,
            'kpi' => [
                'layer_count'   => $layerCount,
                'mass_total'    => $massTotal,
                'a1a3_total'    => $a1a3Total,
                'u_value'       => $uValue,             // computed from layers if possible
                'life_years'    => $life,
            ],
            'layers'    => $rows,
            'chartRows' => $chartRows,
        ];
    }

    public function render()
    {
        return view('livewire.environmental.system-browser');
    }
}
