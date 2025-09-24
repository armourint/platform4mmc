<?php

namespace App\Livewire\Admin;

use App\Models\Manufacturer;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ManufacturersTable extends Component
{
    use WithPagination;

    #[Url] public ?string $county = null;
    #[Url] public ?string $category = null;
    #[Url] public ?string $active = null; // '1'|'0'|null
    #[Url] public string $search = '';

    public function render()
    {
        $q = Manufacturer::query()
            ->when($this->county, fn($q)=>$q->where('county_code',$this->county))
            ->when($this->category, fn($q)=>$q->where('product_category',$this->category))
            ->when($this->active !== null && $this->active !== '', fn($q)=>$q->where('is_active', (bool)$this->active))
            ->when($this->search, fn($q)=>$q->where(function($q){
                $q->where('name','like',"%{$this->search}%")
                  ->orWhere('product_subcategory','like',"%{$this->search}%")
                  ->orWhere('address','like',"%{$this->search}%");
            }))
            ->orderBy('name');

        return view('livewire.admin.manufacturers-table', [
            'rows' => $q->paginate(25),
        ])->layout('admin');
    }
}
