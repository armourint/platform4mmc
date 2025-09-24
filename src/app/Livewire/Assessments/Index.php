<?php

namespace App\Livewire\Assessments;

use App\Models\Assessment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')] // ⬅️ fix here
class Index extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $project = '';

    public function updatingSearch()  { $this->resetPage(); }
    public function updatingStatus()  { $this->resetPage(); }
    public function updatingProject() { $this->resetPage(); }

    public function render()
    {
        $q = Assessment::query()
            ->with(['project:id,name'])
            ->when($this->search !== '', function ($q) {
                $s = $this->search;
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                       ->orWhere('system_code', 'like', "%{$s}%");
                });
            })
            ->when($this->status !== '', fn($q) => $q->where('status', $this->status))
            ->when($this->project !== '', fn($q) => $q->where('project_id', (int)$this->project))
            ->latest();

        $assessments = $q->paginate(12);
        $projects = \App\Models\Project::query()->orderBy('name')->pluck('name','id');

        return view('livewire.assessments.index', compact('assessments','projects'));
    }
}
