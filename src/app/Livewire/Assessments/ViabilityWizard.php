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

    // Form fields
    public string $residential_type = '';          // low|medium|high (radio)
    public string $storage_type = '';              // on-site|off-site (radio)
    public array  $machinery = [];                 // ['tower_crane','telescopic_crane','telehandler'] (checkboxes)

    public ?int   $stories = null;                 // integer stepper
    public ?int   $height_m = null;                // auto: stories * 3 (integer)
    public ?int   $res_units = null;               // integer
    public bool   $has_commercial = false;         // checkbox shows commercial_units
    public ?int   $commercial_units = null;        // integer
    public ?int   $storage_space_m2 = null;        // integer

    // Optional capacities (shown when corresponding machinery is checked)
    public ?int   $tower_crane_capacity_t = null;       // integer (tonnes)
    public ?int   $telescopic_crane_capacity_t = null;  // integer (tonnes)
    public ?int   $telehandler_capacity_t = null;       // integer (tonnes)

    protected function rules(): array
    {
        return [
            'residential_type'               => ['required', 'in:low,medium,high'],
            'storage_type'                   => ['required', 'in:on-site,off-site'],
            'machinery'                      => ['array'],

            'stories'                        => ['nullable', 'integer', 'min:0'],
            'height_m'                       => ['nullable', 'integer', 'min:0'],
            'res_units'                      => ['nullable', 'integer', 'min:0'],

            'has_commercial'                 => ['boolean'],
            'commercial_units'               => ['nullable', 'integer', 'min:0'],

            'storage_space_m2'               => ['nullable', 'integer', 'min:0'],

            'tower_crane_capacity_t'         => ['nullable', 'integer', 'min:0'],
            'telescopic_crane_capacity_t'    => ['nullable', 'integer', 'min:0'],
            'telehandler_capacity_t'         => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    /**
     * Fires while typing (bound in Blade via wire:input).
     */
    public function syncHeightFromStories(): void
    {
        $v = (int) ($this->stories ?? 0);
        if ($v < 0) $v = 0;
        $this->stories  = $v;
        $this->height_m = $v > 0 ? $v * 3 : 0;
    }

    /**
     * Livewire lifecycle hook – also runs whenever "stories" updates,
     * even if the template event were missing.
     */
    public function updatedStories($value): void
    {
        if ($value === '' || $value === null) {
            $this->stories  = null;
            $this->height_m = null;
            return;
        }

        $v = max(0, (int) $value);
        $this->stories  = $v;
        $this->height_m = $v * 3; // overwrites any manual height
    }

    /**
     * Normalise manual edits to height.
     * Next change to stories will overwrite this with stories*3.
     */
    public function updatedHeightM($value): void
    {
        if ($value === '' || $value === null) {
            $this->height_m = null;
            return;
        }
        $this->height_m = max(0, (int) $value);
    }

    public function evaluate(ViabilityEvaluator $svc)
    {
        $this->validate();

        // current published dataset for Viability
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
            'residential_type'               => $this->residential_type,
            'storage_type'                   => $this->storage_type,
            'machinery'                      => $this->machinery,

            'stories'                        => $this->stories,
            'height_m'                       => $this->height_m,
            'res_units'                      => $this->res_units,

            'has_commercial'                 => (bool) $this->has_commercial,
            'commercial_units'               => $this->commercial_units,
            'storage_space_m2'               => $this->storage_space_m2,

            'tower_crane_capacity_t'         => $this->tower_crane_capacity_t,
            'telescopic_crane_capacity_t'    => $this->telescopic_crane_capacity_t,
            'telehandler_capacity_t'         => $this->telehandler_capacity_t,
        ];

        $result = $svc->evaluate($inputs, $dv);

        $assessment = Assessment::create([
            'project_id'         => $this->project->id,
            'type'               => 'viability',
            'dataset_version_id' => $result['dataset_version_id'] ?? $dv->id,
            'inputs'             => $inputs,
            'outputs'            => $result,
            'status'             => 'completed',
        ]);

        foreach (($result['per_system'] ?? []) as $code => $res) {
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
        return view('livewire.assessments.viability-wizard', [
            'project' => $this->project,
        ]);
    }
}
