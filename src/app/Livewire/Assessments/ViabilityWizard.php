<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Models\AssessmentSystemResult;
use App\Models\DatasetVersion;
use App\Models\Project;
use App\Models\System;
use App\Services\DST\ViabilityEvaluator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ViabilityWizard extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $residential_type = ''; // low|medium|high
    public string $storage_type = '';     // on-site|off-site
    public array $machinery = [];         // ['tower_crane','telescopic_crane','telehandler']

    public ?int $stories = null;
    public ?float $height_m = null;
    public ?int $res_units = null;

    public bool $has_commercial = false;
    public ?int $commercial_units = null;

    public ?float $storage_space_m2 = null;
    public ?float $tower_crane_capacity_t = null;

    protected function rules(): array
    {
        return [
            'residential_type'       => ['required','in:low,medium,high'],
            'storage_type'           => ['required','in:on-site,off-site'],
            'machinery'              => ['array'],

            'stories'                => ['nullable','integer','min:1'],
            'height_m'               => ['nullable','numeric','min:0'],
            'res_units'              => ['nullable','integer','min:0'],
            'has_commercial'         => ['boolean'],
            'commercial_units'       => ['nullable','integer','min:0'],
            'storage_space_m2'       => ['nullable','numeric','min:0'],
            'tower_crane_capacity_t' => ['nullable','numeric','min:0'],
        ];
    }

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function evaluate(ViabilityEvaluator $svc)
    {
        $this->validate();

        $dv = DatasetVersion::query()
            ->where('module', 'viability')
            ->where('status', 'published')
            ->latest('id')
            ->first();

        if (!$dv) {
            $this->addError('residential_type', 'No published dataset for Viability. Ask an admin to publish one.');
            return;
        }

        $inputs = [
            'residential_type'       => $this->residential_type,
            'storage_type'           => $this->storage_type,
            'machinery'              => $this->machinery,
            'stories'                => $this->stories,
            'height_m'               => $this->height_m,
            'res_units'              => $this->res_units,
            'has_commercial'         => (bool)$this->has_commercial,
            'commercial_units'       => $this->commercial_units,
            'storage_space_m2'       => $this->storage_space_m2,
            'tower_crane_capacity_t' => $this->tower_crane_capacity_t,
        ];

        $result = $svc->evaluate($inputs);

        $assessment = Assessment::create([
            'project_id'         => $this->project->id,
            'type'               => 'viability',
            'dataset_version_id' => $result['dataset_version_id'],
            'inputs'             => $inputs,
            'outputs'            => $result,
            'status'             => 'completed',
        ]);

        foreach ($result['per_system'] as $code => $res) {
            $sid = System::where('code', $code)->value('id');
            if (!$sid) continue;

            AssessmentSystemResult::create([
                'assessment_id' => $assessment->id,
                'system_id'     => $sid,
                'viable'        => (bool)($res['viable'] ?? false),
                'reason'        => $res['reason'] ?? null,
            ]);
        }

        return $this->redirectRoute('assessments.results', $assessment);
    }

    public function render()
    {
        return view('livewire.assessments.viability-wizard', ['project' => $this->project]);
    }
}
