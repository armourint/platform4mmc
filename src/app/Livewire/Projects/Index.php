<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public $projects;

    public function mount()
    {
        $this->projects = Project::query()
            ->where('owner_id', Auth::id())
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.projects.index');
    }
}
