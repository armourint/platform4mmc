<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Models\DatasetVersion;
use App\Models\Project;
use App\Services\DST\ViabilityEvaluator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ViabilityWizard extends Component
{
    public Project $project;

    // Single-select
    public string $residentialType = 'Low Rise';

    // Multi-selects (checkbox arrays)
    public array $storageLocations = [];     // ['On Site Storage','Off Site Storage']
    public array $craneTypes       = [];     // ['Tower Crane','Telescopic Crane','Telehandler Crane']
    public array $truckTypes       = [];     // ['Flatbed Truck','Flatbed A Frame']

    // Single-select "band" radios
    public string $panelHeightBand     = '<=3.0m';   // '<=3.0m' | '>3.0m'
    public string $frameLengthBand     = '<=12.0m';  // '<=12.0m' | '>12.0m'
    public string $frameWidthBand      = '<=3.2m';   // '<=3.2m' | '>3.2m'

    public function mount(Project $project): void
    {
        $this->project = $project;

        // Sensible defaults so form is valid out-of-the-box
        $this->storageLocations = ['On Site Storage'];
        $this->craneTypes       = ['Tower Crane'];
        $this->truckTypes       = ['Flatbed Truck'];
    }

    protected function rules(): array
    {
        return [
            // single
            'residentialType' => ['required', Rule::in(['Low Rise','Medium Rise','High Rise'])],

            // multi (require at least one)
            'storageLocations' => ['required','array','min:1'],
            'storageLocations.*' => [Rule::in(['On Site Storage','Off Site Storage'])],

            'craneTypes' => ['required','array','min:1'],
            'craneTypes.*' => [Rule::in(['Tower Crane','Telescopic Crane','Telehandler Crane'])],

            'truckTypes' => ['required','array','min:1'],
            'truckTypes.*' => [Rule::in(['Flatbed Truck','Flatbed A Frame'])],

            // banded radios
            'panelHeightBand' => ['required', Rule::in(['<=3.0m','>3.0m'])],
            'frameLengthBand' => ['required', Rule::in(['<=12.0m','>12.0m'])],
            'frameWidthBand'  => ['required', Rule::in(['<=3.2m','>3.2m'])],
        ];
    }

    public function save()
    {
        $this->validate();

        // 1) Get latest published viability dataset
        $dv = DatasetVersion::query()
            ->where('module', 'viability')
            ->where('status', 'published')
            ->latest('id')
            ->first();

        if (!$dv) {
            $this->addError('dataset', 'No published Viability dataset found. Ask an admin to import & publish one.');
            return;
        }

        // 2) Normalize wizard inputs to evaluator/rules schema
        $normalized = $this->normalizedInputs();

        // 3) Evaluate (IMPORTANT: pass the DatasetVersion model, not its id)
        /** @var ViabilityEvaluator $svc */
        $svc = app(ViabilityEvaluator::class);
        $result = $svc->evaluate($normalized, $dv);

        // 4) Persist assessment
        $assessment = Assessment::create([
            'project_id'         => $this->project->id,
            'type'               => 'viability',
            'dataset_version_id' => $dv->id,
            'inputs'             => $normalized,
            'outputs'            => $result,
        ]);

        return redirect()->route('assessments.results', $assessment);
    }

    private function normalizedInputs(): array
    {
        // Map pretty → enum
        $residential = match ($this->residentialType) {
            'Low Rise'    => 'low',
            'Medium Rise' => 'medium',
            'High Rise'   => 'high',
            default       => 'low',
        };

        // Multi → normalized arrays
        $storageTypes = array_values(array_unique(array_map(function ($v) {
            return match ($v) {
                'On Site Storage'  => 'on-site',
                'Off Site Storage' => 'off-site',
                default            => null,
            };
        }, $this->storageLocations)));
        $storageTypes = array_values(array_filter($storageTypes));

        $machinery = array_values(array_unique(array_map(function ($v) {
            return match ($v) {
                'Tower Crane'        => 'tower_crane',
                'Telescopic Crane'   => 'telescopic_crane',
                'Telehandler Crane'  => 'telehandler',
                default              => null,
            };
        }, $this->craneTypes)));
        $machinery = array_values(array_filter($machinery));

        $truckTypes = array_values(array_unique(array_map(function ($v) {
            return match ($v) {
                'Flatbed Truck'   => 'flatbed_truck',
                'Flatbed A Frame' => 'flatbed_a_frame',
                default           => null,
            };
        }, $this->truckTypes)));
        $truckTypes = array_values(array_filter($truckTypes));

        // Bands (for rules) + numeric proxies (for legacy operators)
        [$panelBand, $panelNum]   = $this->bandToPair($this->panelHeightBand, 3.0);
        [$lengthBand, $lengthNum] = $this->bandToPair($this->frameLengthBand, 12.0);
        [$widthBand,  $widthNum]  = $this->bandToPair($this->frameWidthBand, 3.2);

        // For legacy compatibility:
        // - keep 'machinery' as array (already expected)
        // - add *plural* arrays: storage_types, truck_types (NEW)
        // - keep single 'storage_type' and 'truck_type' as first selection (if any), for older rules
        return [
            // Enums / arrays
            'residential_type' => $residential,
            'storage_types'    => $storageTypes,                 // NEW: arrays for multi
            'machinery'        => $machinery,                    // array (existing)
            'truck_types'      => $truckTypes,                   // NEW: arrays for multi

            // Legacy single fields (first selected, if any) for backward-compat rules datasets
            'storage_type'     => $storageTypes[0] ?? null,
            'truck_type'       => $truckTypes[0]   ?? null,

            // Bands for rules (easier than numeric ops with bucketed input)
            'panel_height_band'     => $panelBand,               // '<=3.0m' | '>3.0m'
            'max_frame_length_band' => $lengthBand,              // '<=12.0m' | '>12.0m'
            'max_frame_width_band'  => $widthBand,               // '<=3.2m' | '>3.2m'

            // Numeric proxies (if your evaluator currently only supports gte/lte on numbers)
            'panel_height_m'       => $panelNum,
            'max_frame_length_m'   => $lengthNum,
            'max_frame_width_m'    => $widthNum,
        ];
    }

    /**
     * Convert a band label into [band_string, numeric_proxy]
     * Example: '<=3.0m' → ['<=3.0m', 3.0];  '>3.0m' → ['>3.0m', 3.01]
     */
    private function bandToPair(string $band, float $threshold): array
    {
        $band = trim($band);
        if (str_starts_with($band, '<=')) {
            return [$band, $threshold];
        }
        // tiny epsilon above the threshold for ">" bands so legacy lte/gte rules still behave
        return [$band, $threshold + 0.01];
    }

    public function render()
    {
        return view('livewire.assessments.viability-wizard');
    }
}
