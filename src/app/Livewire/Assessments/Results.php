<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Assessment;
use App\Models\AssessmentSystemResult;
use App\Models\System;

class Results extends Component
{
    public Assessment $assessment;
    public $viable = [];
    public $notViable = [];
    public $env = null;

    public function mount(Assessment $assessment)
    {
        $this->assessment = $assessment->load('project');

        if ($assessment->type === 'viability') {
            $rows = AssessmentSystemResult::with('system')
                ->where('assessment_id', $assessment->id)->get();

            $this->viable = $rows->where('is_viable', true)->pluck('system.name')->all();
            $this->notViable = $rows->where('is_viable', false)->map(function($r){
                return ['name'=>$r->system->name, 'reason'=>$r->reason];
            })->values()->all();
        } else {
            $this->env = $assessment->score ?? [];
        }
    }

    public function render()
    {
        return view('livewire.assessments.results');
    }
}
