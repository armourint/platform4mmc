<?php

namespace App\Livewire\Admin\Knowledge;

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin')]
class EditCategory extends Component
{
    public Category $category;
    public string $name = '';
    public string $type = '';

    // Bound by {category:id} in route
    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name     = $category->name;
        $this->type     = $category->type ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => ['required','string','max:100'],
            'type' => ['nullable','string','max:50'],
        ];
    }

    public function save()
    {
        $this->validate();

        $slug = Str::slug($this->name);

        $this->category->update([
            'name' => $this->name,
            'type' => $this->type ?: null,
            'slug' => $slug,
        ]);

        // Ensure slug uniqueness
        if (Category::where('slug', $this->category->slug)->where('id', '!=', $this->category->id)->exists()) {
            $this->category->slug = $slug . '-' . $this->category->id;
            $this->category->save();
        }

        session()->flash('success', 'Category updated.');
        return redirect()->route('admin.knowledge.categories.index');
    }

    public function render()
    {
        return view('livewire.admin.knowledge.edit-category');
    }
}
