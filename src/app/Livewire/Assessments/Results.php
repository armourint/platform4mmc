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
    public array $envLayers = [];     // per-layer table
    public array $hotspots = [];      // top contributors

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
        // existing viability prep (unchanged)
        $out = $this->assessment->outputs ?? [];
        $this->summary  = Arr::get($out, 'summary', []);
        $this->included = collect(Arr::get($out, 'included', []))->values()->all();
        $this->excluded = collect(Arr::get($out, 'excluded', []))->values()->all();
    }

    protected function prepareEnvironmental(): void
    {
        $inputs = $this->assessment->inputs ?? [];
        $this->datasetVersionId = $this->assessment->dataset_version_id ?: null;
        $this->systemCode       = Arr::get($inputs, 'selected_system_code');

        // 1) Try to load per-layer rows for the assessment's own dataset_version_id.
        $layers = collect();
        if ($this->datasetVersionId && $this->systemCode) {
            $layers = EnvironmentalLayer::query()
                ->where('dataset_version_id', $this->datasetVersionId)
                ->where('system_code', $this->systemCode)
                ->orderBy('layer_no')
                ->get();
        }

        if ($layers->isNotEmpty()) {
            // 2) Compute totals from dataset rows
            $a1a3 = (float) round($layers->sum('a1a3_per_m2'), 6);
            $a4a5 = 0.0; // (optional) if you later add factors, sum them here
            $c1c4 = 0.0; // (optional) ditto

            $this->envTotals = [
                'a1_a3' => $a1a3,
                'a4_a5' => $a4a5,
                'c1_c4' => $c1c4,
                'sum'   => $a1a3 + $a4a5 + $c1c4,
            ];

            // Prepare table rows for blade
            $this->envLayers = $layers->map(function (EnvironmentalLayer $r) {
                return [
                    'layer_no'         => $r->layer_no,
                    'functional_role'  => $r->functional_role,
                    'generic_material' => $r->generic_material,
                    'mass_kg_m2'       => $r->mass_kg_m2,
                    'a1a3_per_m2'      => $r->a1a3_per_m2,
                    'carbon_factor'    => $r->carbon_factor,
                ];
            })->values()->all();

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

            $this->summary = [
                'status' => 'OK',
                'note'   => 'Layer dataset used',
            ];
            return;
        }

        // 3) Fallback to typed totals saved with the assessment
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

        $this->summary = [
            'status' => 'OK',
            'note'   => 'No layer dataset linked. Totals shown from inputs.',
        ];
    }

    public function render()
    {
        return view('livewire.assessments.results');
    }
}
