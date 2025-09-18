<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Project;
use App\Models\Assessment;

class ViabilityWizard extends Component
{
    public Project $project;

    // Example wizard state (replace with real fields)
    public $storey_count;
    public $site_constraints;
    public $target_nzeb;

    protected $rules = [
        'storey_count'    => ['required','integer','min:1','max:50'],
        'site_constraints'=> ['nullable','string','max:1000'],
        'target_nzeb'     => ['required','boolean'],
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->target_nzeb = false;
    }

    public function save()
    {
        $this->validate();

        // Persist a basic "viability" assessment record (adjust to your schema)
        $assessment = Assessment::create([
            'project_id' => $this->project->id,
            'type'       => 'viability',
            'inputs'     => [
                'storey_count' => $this->storey_count,
                'site_constraints' => $this->site_constraints,
                'target_nzeb' => (bool) $this->target_nzeb,
            ],
            'results'    => null, // scoring handled later
        ]);

        return redirect()->route('assessments.results', $assessment)
            ->with('status', 'Viability assessment created');
    }

    public function render()
    {
        return view('livewire.assessments.viability-wizard')
            ->layout('layouts.app', [
                'header' => 'Viability Assessment — ' . $this->project->name,
            ]);
    }
}
