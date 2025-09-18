<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $name = '';
    public ?string $client = null;
    public ?string $location = null;
    public ?string $user_role = null;

    protected function rules(): array
    {
        return [
            'name'      => ['required','string','max:255'],
            'client'    => ['nullable','string','max:255'],
            'location'  => ['nullable','string','max:255'],
            'user_role' => ['nullable','string','max:255'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        $project = Project::create([
            'owner_id' => auth()->id(),
            'name'     => $data['name'],
            'client'   => $data['client'] ?? null,
            'location' => $data['location'] ?? null,
            'meta'     => ['user_role' => $data['user_role'] ?? null],
        ]);

        $this->redirectRoute('assessments.hub', $project);
    }

    public function render()
    {
        return view('livewire.projects.create');
    }
}
