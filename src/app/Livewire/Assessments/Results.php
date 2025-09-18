<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Assessment;

class Results extends Component
{
    public Assessment $assessment;

    public function mount(Assessment $assessment)
    {
        $this->assessment = $assessment->load('project');
    }

    public function render()
    {
        // You will plug in real scoring here; for now, read $assessment->results or show placeholder.
        $results = $this->assessment->results;

        return view('livewire.assessments.results', [
                'results' => $results,
            ])
            ->layout('layouts.app', [
                'header' => 'Results — ' . ucfirst($this->assessment->type) . ' · ' . $this->assessment->project->name,
            ]);
    }
}
