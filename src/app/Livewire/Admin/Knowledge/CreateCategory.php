<?php

namespace App\Livewire\Admin\Knowledge;

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin')]
class CreateCategory extends Component
{
    public string $name = '';
    public string $type = '';

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

        $category = Category::create([
            'name' => $this->name,
            'type' => $this->type ?: null,
            'slug' => $slug,
        ]);

        // Ensure slug uniqueness
        if (Category::where('slug', $category->slug)->where('id', '!=', $category->id)->exists()) {
            $category->slug = $slug . '-' . $category->id;
            $category->save();
        }

        session()->flash('success', 'Category created.');
        return redirect()->route('admin.knowledge.categories.index');
    }

    public function render()
    {
        return view('livewire.admin.knowledge.create-category');
    }
}
