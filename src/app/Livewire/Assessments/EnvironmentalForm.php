<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Models\DatasetVersion;
use App\Services\Assessments\EnvironmentalCalculator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EnvironmentalForm extends Component
{
    /** Route param support (e.g. /projects/{project}/environmental) */
    public ?int $projectId = null;

    // Inputs
    public ?float $a1_a3 = null;
    public ?float $a4_a5 = null;
    public ?float $c1_c4 = null;

    public ?float $u_value = null;
    public ?string $ber_rating = null;

    public ?int $reuse_potential = null;        // %
    public ?int $material_recyclability = null; // %

    // Optional snapshot method
    public ?string $selected_system_code = null; // e.g. BLOCK|ICF|LGS|TIMBER

    // Helpful UI notice (not a session flash—Livewire re-render)
    public ?string $notice = null;

    // Cache the DV id we’re working with
    public ?int $datasetVersionId = null;

    /** Accepts either a Project model (route-model bound) or id */
    public function mount($project = null): void
    {
        if (is_object($project) && property_exists($project, 'id')) {
            $this->projectId = (int) $project->id;
        } elseif (is_numeric($project)) {
            $this->projectId = (int) $project;
        }

        $dv = DatasetVersion::query()
            ->where('module', 'environmental')
            ->where('status', 'published')
            ->latest('id')
            ->first();

        $this->datasetVersionId = $dv?->id;
    }

    public function rules(): array
    {
        return [
            'a1_a3' => ['nullable','numeric','min:0'],
            'a4_a5' => ['nullable','numeric','min:0'],
            'c1_c4' => ['nullable','numeric','min:0'],
            'u_value' => ['nullable','numeric','min:0'],
            'ber_rating' => ['nullable','in:A1,A2,A3,B1,B2,B3,C1,C2,C3,D1,D2,E1,E2,F,G'],
            'reuse_potential' => ['nullable','integer','between:0,100'],
            'material_recyclability' => ['nullable','integer','between:0,100'],
            'selected_system_code' => ['nullable','string','max:32'],
        ];
    }

    /**
     * TEMP: Populate fields with mid-range values based on the selected method.
     * Defaults to TIMBER when none selected.
     */
    public function populateSample(): void
    {
        $code = strtoupper((string)($this->selected_system_code ?: 'TIMBER'));

        $samples = [
            'BLOCK' => [
                'a1_a3' => 220.0, 'a4_a5' => 20.0, 'c1_c4' => 70.0,
                'u_value' => 0.18, 'ber' => 'A3', 'reuse' => 5, 'recycle' => 40,
            ],
            'ICF' => [
                'a1_a3' => 160.0, 'a4_a5' => 15.0, 'c1_c4' => 30.0,
                'u_value' => 0.15, 'ber' => 'A2', 'reuse' => 10, 'recycle' => 50,
            ],
            'LGS' => [
                'a1_a3' => 170.0, 'a4_a5' => 18.0, 'c1_c4' => 35.0,
                'u_value' => 0.18, 'ber' => 'A2', 'reuse' => 15, 'recycle' => 90,
            ],
            'TIMBER' => [
                'a1_a3' => 90.0, 'a4_a5' => 12.0, 'c1_c4' => 20.0,
                'u_value' => 0.15, 'ber' => 'A2', 'reuse' => 30, 'recycle' => 60,
            ],
        ];

        $s = $samples[$code] ?? $samples['TIMBER'];

        $this->a1_a3 = $s['a1_a3'];
        $this->a4_a5 = $s['a4_a5'];
        $this->c1_c4 = $s['c1_c4'];

        $this->u_value = $s['u_value'];
        $this->ber_rating = $s['ber'];

        $this->reuse_potential = $s['reuse'];
        $this->material_recyclability = $s['recycle'];

        // Ensure dropdown reflects the method we’re sampling
        $this->selected_system_code = $code;

        $this->notice = "Sample values populated for {$code}.";
    }

    public function save()
    {
        $this->validate();

        $dv = $this->datasetVersionId
            ? DatasetVersion::find($this->datasetVersionId)
            : DatasetVersion::where('module','environmental')->where('status','published')->latest('id')->first();

        if (!$dv) {
            $this->notice = 'No published Environmental dataset found. Please import & publish one.';
            return;
        }

        $inputs = [
            'a1_a3' => $this->a1_a3 ?? 0.0,
            'a4_a5' => $this->a4_a5 ?? 0.0,
            'c1_c4' => $this->c1_c4 ?? 0.0,
            'u_value' => $this->u_value,
            'ber_rating' => $this->ber_rating,
            'reuse_potential' => $this->reuse_potential,
            'material_recyclability' => $this->material_recyclability,
            'selected_system_code' => $this->selected_system_code,
            'project_id' => $this->projectId,
        ];

        $total = (float) ($inputs['a1_a3'] + $inputs['a4_a5'] + $inputs['c1_c4']);

        $outputs = [
            'total_co2' => $total,
            'breakdown' => [
                'a1_a3' => $inputs['a1_a3'],
                'a4_a5' => $inputs['a4_a5'],
                'c1_c4' => $inputs['c1_c4'],
            ],
            'envelope' => [
                'u_value' => $inputs['u_value'],
                'ber_rating' => $inputs['ber_rating'],
            ],
            'eol' => [
                'reuse_potential' => $inputs['reuse_potential'],
                'material_recyclability' => $inputs['material_recyclability'],
            ],
        ];

        // Optional layer snapshot from published dataset
        if ($this->selected_system_code) {
            /** @var EnvironmentalCalculator $calc */
            $calc = app(EnvironmentalCalculator::class);
            $snapshot = $calc->snapshotForSystem($dv, $this->selected_system_code);
            $outputs['layer_snapshot'] = $snapshot;
        }

        $assessment = Assessment::create([
            'type' => 'environmental',
            'dataset_version_id' => $dv->id,
            'project_id' => $this->projectId,
            'inputs' => $inputs,
            'outputs' => $outputs,
        ]);

        return redirect()->route('assessments.results', $assessment);
    }

    public function render()
    {
        // restrict to current DV if set
        $query = DB::table('environmental_layers')->distinct()->select('system_code');
        if ($this->datasetVersionId) {
            $query->where('dataset_version_id', $this->datasetVersionId);
        }
        $systemCodes = $query->orderBy('system_code')->pluck('system_code')->values()->all();

        return view('livewire.assessments.environmental-form', [
            'systemCodes' => $systemCodes,
        ]);
    }
}
