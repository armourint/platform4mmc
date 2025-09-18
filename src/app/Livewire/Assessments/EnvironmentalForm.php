<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Project;
use App\Models\Assessment;

class EnvironmentalForm extends Component
{
    public Project $project;

    // Placeholder inputs; replace with real fields (e.g., materials, system choice, etc.)
    public $embodied_carbon_target;
    public $lca_scope; // e.g., A1–A3 only, A–C, etc.

    protected $rules = [
        'embodied_carbon_target' => ['nullable','numeric','min:0'],
        'lca_scope'              => ['nullable','string','max:255'],
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function save()
    {
        $this->validate();

        $assessment = Assessment::create([
            'project_id' => $this->project->id,
            'type'       => 'environmental',
            'inputs'     => [
                'embodied_carbon_target' => $this->embodied_carbon_target,
                'lca_scope' => $this->lca_scope,
            ],
            'results'    => null,
        ]);

        return redirect()->route('assessments.results', $assessment)
            ->with('status', 'Environmental assessment created');
    }

    public function render()
    {
        return view('livewire.assessments.environmental-form')
            ->layout('layouts.app', [
                'header' => 'Environmental Assessment — ' . $this->project->name,
            ]);
    }
}
