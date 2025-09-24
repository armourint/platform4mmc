<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use App\Services\Assessments\ResultsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')] // ⬅️ fix here
class Results extends Component
{
    public Assessment $assessment;

    public ?string $systemCode = null;
    public array $viability = [];
    public array $env = [];

    public function mount(Assessment $assessment): void
    {
        $this->assessment = $assessment;
        $this->systemCode = ResultsService::resolveSystemCode($assessment);
        $this->refreshResults();
    }

    public function refreshResults(): void
    {
        $this->viability = ResultsService::viabilitySummary($this->systemCode);
        $this->env       = ResultsService::environmentalSnapshot($this->systemCode);
    }

    public function render()
    {
        return view('livewire.assessments.results');
    }
}
