<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public function render()
    {
        $projects = Project::where('owner_id', Auth::id())->latest()->get();

        return view('livewire.projects.index', compact('projects'))
            ->layout('layouts.app', ['header' => 'Projects']);
    }
}
