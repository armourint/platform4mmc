<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Project;
use App\Models\Assessment;

class Hub extends Component
{
    public Project $project;
    public $assessmentsByType;

    public function mount(Project $project)
    {
        $this->project = $project->load(['assessments']);
        $this->assessmentsByType = $this->project->assessments->groupBy('type');
    }

    public function render()
    {
        return view('livewire.assessments.hub');
    }
}
