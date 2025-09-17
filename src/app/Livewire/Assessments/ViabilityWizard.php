<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Project;
use App\Models\Assessment;
use App\Models\AssessmentSystemResult;
use App\Models\DatasetVersion;
use App\Services\DST\ViabilityEvaluator;

class ViabilityWizard extends Component
{
    public Project $project;

    // MVP inputs (aligns with prototype)
    public $residential_type = 'low'; // low|medium|high
    public $storage_type = 'on-site'; // on-site|off-site
    public $machinery = [];           // ['tower_crane','telehandler',...]

    protected $rules = [
        'residential_type' => ['required','in:low,medium,high'],
        'storage_type'     => ['required','in:on-site,off-site'],
        'machinery'        => ['array'],
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function evaluate(ViabilityEvaluator $evaluator)
    {
        $this->validate();

        $dv = DatasetVersion::where('module','viability')
            ->where('status','published')
            ->orderByDesc('effective_from')
            ->first();

        if (!$dv) {
            $this->addError('residential_type', 'No published dataset for Viability. Ask an admin to publish one.');
            return;
        }

        $inputs = [
            'residential_type' => $this->residential_type,
            'storage_type' => $this->storage_type,
            'machinery' => $this->machinery,
        ];

        $result = $evaluator->evaluate($inputs, $dv);

        $assessment = Assessment::create([
            'project_id' => $this->project->id,
            'type' => 'viability',
            'status' => 'completed',
            'dataset_version_id' => $dv->id,
            'inputs' => $inputs,
            'score' => $result['score'],
        ]);

        foreach ($result['score']['per_system'] as $systemId => $row) {
            AssessmentSystemResult::create([
                'assessment_id' => $assessment->id,
                'system_id' => $systemId,
                'is_viable' => (bool)($row['ok'] ?? false),
                'reason' => $row['reason'] ?? null,
            ]);
        }

        return redirect()->route('assessments.results', $assessment);
    }

    public function render()
    {
        return view('livewire.assessments.viability-wizard');
    }
}
