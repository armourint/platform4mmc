<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class Create extends Component
{
    public $name;
    public $client;
    public $location;

    protected $rules = [
        'name' => ['required','string','max:255'],
        'client' => ['nullable','string','max:255'],
        'location' => ['nullable','string','max:255'],
    ];

    public function save()
    {
        $this->validate();

        $project = Project::create([
            'owner_id' => Auth::id(),
            'name' => $this->name,
            'client' => $this->client,
            'location' => $this->location,
            'meta' => [],
        ]);

        return redirect()->route('assessments.hub', $project)
            ->with('status', 'Project created');
    }

    public function render()
    {
        return view('livewire.projects.create')
            ->layout('layouts.app', ['header' => 'Create Project']);
    }
}
