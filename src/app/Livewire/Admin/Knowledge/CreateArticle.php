<?php

namespace App\Livewire\Admin\Knowledge;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin')]
class CreateArticle extends Component
{
    public string $title = '';
    public string $body = '';
    public string $status = 'draft';
    public array $categoryIds = [];

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

        $article = Article::create([
            'title'  => $this->title,
            'body'   => $this->body,
            'status' => $this->status,
        ]);

        $article->categories()->sync($this->categoryIds);

        session()->flash('success', 'Article created.');
        return redirect()->route('admin.knowledge.articles.index');
    }

    public function render()
    {
        return view('livewire.admin.knowledge.create-article', [
            'categories' => Category::orderBy('type')->orderBy('name')->get(),
        ]);
    }
}
