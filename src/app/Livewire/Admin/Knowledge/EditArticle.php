<?php

namespace App\Livewire\Admin\Knowledge;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin')]
class EditArticle extends Component
{
    public Article $article;
    public string $title = '';
    public string $body = '';
    public string $status = 'draft';
    public array $categoryIds = [];

    // Bound by {article:id} in route
    public function mount(Article $article): void
    {
        $this->article     = $article;
        $this->title       = $article->title;
        $this->body        = $article->body ?? '';
        $this->status      = $article->status ?? 'draft';
        $this->categoryIds = $article->categories()->pluck('categories.id')->toArray();
    }

    protected function rules(): array
    {
        return [
            'title'        => ['required','string','max:255'],
            'body'         => ['nullable','string'],
            'status'       => ['required', Rule::in(['draft','published'])],
            'categoryIds'  => ['array'],
            'categoryIds.*'=> ['exists:categories,id'],
        ];
    }

    public function save()
    {
        $this->validate();

        $this->article->update([
            'title'  => $this->title,
            'body'   => $this->body,
            'status' => $this->status,
        ]);

        $this->article->categories()->sync($this->categoryIds);

        session()->flash('success', 'Article updated.');
        return redirect()->route('admin.knowledge.articles.index');
    }

    public function render()
    {
        return view('livewire.admin.knowledge.edit-article', [
            'categories' => Category::orderBy('type')->orderBy('name')->get(),
        ]);
    }
}
