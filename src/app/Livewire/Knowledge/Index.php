<?php

namespace App\Livewire\Knowledge;

use App\Models\Article;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]  // use main app layout (includes header/footer)
class Index extends Component
{
    use WithPagination;

    public string $q = '';              // search query
    public ?int $categoryId = null;     // filter by category

    // Reset to page 1 when filters change
    public function updatingQ()      { $this->resetPage(); }
    public function updatingCategoryId() { $this->resetPage(); }

    public function render()
    {
        // Build query for published articles
        $query = Article::published()
            ->when($this->q !== '', function($q) {
                $q->where(function($sub) {
                    $sub->where('title', 'like', "%{$this->q}%")
                        ->orWhere('body', 'like', "%{$this->q}%");
                });
            })
            ->when($this->categoryId, function($q) {
                $q->whereHas('categories', fn($sub) => $sub->where('categories.id', $this->categoryId));
            })
            ->orderBy('published_at', 'desc')
            ->orderBy('title');

        $articles = $query->paginate(10);

        // Fetch categories for the filter dropdown (grouped by type)
        $categories = Category::orderBy('type')->orderBy('name')->get();

        return view('livewire.knowledge.index', [
            'articles'   => $articles,
            'categories' => $categories,
            'total'      => Article::published()->count()
        ]);
    }
}

