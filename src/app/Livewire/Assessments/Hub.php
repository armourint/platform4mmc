<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Project;
use App\Models\Assessment;

class Hub extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        // Load this project's assessments (if any)
        $assessments = Assessment::where('project_id', $this->project->id)
            ->latest()
            ->get();

        return view('livewire.assessments.hub', [
                'assessments' => $assessments,
            ])
            ->layout('layouts.app', [
                'header' => 'Assessments: ' . $this->project->name,
            ]);
    }
}
