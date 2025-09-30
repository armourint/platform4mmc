<?php

namespace App\Livewire\Knowledge;

use App\Models\Article;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Show extends Component
{
    public Article $article;  // the article to display

    public function mount(Article $article) {
        // Ensure we only show published articles (if unpublished slug is hit)
        if ($article->status !== 'published') {
            abort(404);
        }
        $this->article = $article;
    }

    public function render()
    {
        return view('livewire.knowledge.show', [
            'article' => $this->article,
        ]);
    }
}
