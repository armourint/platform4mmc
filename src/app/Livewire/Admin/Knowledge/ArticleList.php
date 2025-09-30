<?php

namespace App\Livewire\Admin\Knowledge;

use App\Models\Article;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('admin')]  // use admin layout
class ArticleList extends Component
{
    use WithPagination;
    public string $search = '';
    public ?string $statusFilter = null; // e.g. 'published' or 'draft' or null for all

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function render()
    {
        $query = Article::query()->with('categories')->orderBy('created_at', 'desc');
        if($this->search !== '') {
            $query->where('title','like',"%{$this->search}%");
        }
        if($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        $articles = $query->paginate(10);
        return view('livewire.admin.knowledge.article-list', [
            'articles' => $articles
        ]);
    }

    // Delete action (soft delete if enabled, or force delete)
    public function deleteArticle($articleId) {
        $article = Article::find($articleId);
        if($article) {
            $article->delete(); // will soft-delete if SoftDeletes is used
            $this->resetPage(); // refresh list
        }
    }
}
