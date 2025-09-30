<?php

namespace App\Livewire\Admin\Knowledge;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('admin')]
class CategoryList extends Component
{
    use WithPagination;
    public string $search = '';

    public function updatingSearch() { $this->resetPage(); }

    public function render()
    {
        $query = Category::query()->orderBy('type')->orderBy('name');
        if($this->search !== '') {
            $query->where('name', 'like', "%{$this->search}%")
                  ->orWhere('type', 'like', "%{$this->search}%");
        }
        $categories = $query->paginate(15);
        return view('livewire.admin.knowledge.category-list', [
            'categories' => $categories
        ]);
    }

    public function deleteCategory($id)
    {
        $cat = Category::find($id);
        if($cat) {
            $cat->delete();
            $this->resetPage();
        }
    }
}
