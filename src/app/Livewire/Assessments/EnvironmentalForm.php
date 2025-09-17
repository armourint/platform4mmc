<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Project;
use App\Models\Assessment;
use App\Models\DatasetVersion;

class EnvironmentalForm extends Component
{
    public Project $project;

    public $a1_a3; public $a4_a5; public $c1_c4;
    public $reuse_pct; public $recycle_pct;
    public $area_m2;

    protected $rules = [
        'a1_a3' => ['required','numeric','min:0'],
        'a4_a5' => ['required','numeric','min:0'],
        'c1_c4' => ['required','numeric','min:0'],
        'reuse_pct' => ['nullable','numeric','between:0,100'],
        'recycle_pct' => ['nullable','numeric','between:0,100'],
        'area_m2' => ['nullable','numeric','min:0.01'],
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->reuse_pct = 0;
        $this->recycle_pct = 0;
    }

    public function calculate()
    {
        $this->validate();

        $dv = DatasetVersion::where('module','environmental')
            ->where('status','published')
            ->orderByDesc('effective_from')
            ->first();

        // dv may be null for MVP stub — that's fine, we store without it if needed
        $total = $this->a1_a3 + $this->a4_a5 + $this->c1_c4;
        $per_m2 = $this->area_m2 ? round($total / max($this->area_m2, 0.01), 3) : null;

        $inputs = [
            'a1_a3' => $this->a1_a3,
            'a4_a5' => $this->a4_a5,
            'c1_c4' => $this->c1_c4,
            'reuse_pct' => $this->reuse_pct,
            'recycle_pct' => $this->recycle_pct,
            'area_m2' => $this->area_m2,
        ];

        $score = [
            'total_kgco2e' => $total,
            'kgco2e_per_m2' => $per_m2,
            'breakdown' => [
                'A1-A3' => $this->a1_a3,
                'A4-A5' => $this->a4_a5,
                'C1-C4' => $this->c1_c4,
            ]
        ];

        $assessment = Assessment::create([
            'project_id' => $this->project->id,
            'type' => 'environmental',
            'status' => 'completed',
            'dataset_version_id' => $dv?->id ?? \App\Models\DatasetVersion::factory()->create([
                'module'=>'environmental','version_label'=>'mvp','status'=>'draft'
            ])->id,
            'inputs' => $inputs,
            'score' => $score,
        ]);

        return redirect()->route('assessments.results', $assessment);
    }

    public function render()
    {
        return view('livewire.assessments.environmental-form');
    }
}
