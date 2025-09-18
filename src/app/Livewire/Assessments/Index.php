<?php

namespace App\Livewire\Assessments;

use Livewire\Component;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public function render()
    {
        $projects = Project::where('owner_id', Auth::id())->latest()->get();

        return view('livewire.assessments.index', compact('projects'))
            ->layout('layouts.app', ['header' => 'Assessments']);
    }
}
