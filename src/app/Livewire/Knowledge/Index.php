<?php

namespace App\Livewire\Knowledge;

use App\Models\Content;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $q = '';
    public ?string $type = null; // article|case_study|resource

    public function updatingQ()    { $this->resetPage(); }
    public function updatingType() { $this->resetPage(); }

    public function search(): void
    {
        // No-op: re-render with current filters
    }

    public function render()
    {
        $query = Content::query()
            ->when($this->q !== '', function ($query) {
                $q = $this->q;
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                      ->orWhere('excerpt', 'like', "%{$q}%")
                      ->orWhere('body', 'like', "%{$q}%");
                });
            })
            ->when(!empty($this->type), fn ($q) => $q->where('type', $this->type));

        $items = $query->orderByDesc('published_at')
                       ->orderByDesc('id')
                       ->paginate(12);

        $total = Content::count();

        // Populate the type filter from the data you actually have
        $types = Content::query()
            ->select('type')
            ->whereNotNull('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        return view('livewire.knowledge.index', [
            'items' => $items,
            'total' => $total,
            'types' => $types,
        ]);
    }
}
