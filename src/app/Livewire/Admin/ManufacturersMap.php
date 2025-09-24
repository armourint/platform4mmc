<?php

namespace App\Livewire\Admin;

use App\Models\Manufacturer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('admin')]
class ManufacturersMap extends Component
{
    public bool $onlyActive = true;
    public string $county = '';
    public string $category = '';

    /** Build the points array for the map */
    protected function queryPoints(): array
    {
        $county = trim($this->county);
        $category = trim($this->category);

        $rows = Manufacturer::query()
            ->when($this->onlyActive, fn ($q) => $q->where('is_active', true))
            ->when($county !== '', fn ($q) => $q->where('county_code', 'like', strtoupper($county) . '%'))
            ->when($category !== '', function ($q) use ($category) {
                $q->where(function ($qq) use ($category) {
                    $qq->where('product_category', 'like', "%{$category}%")
                       ->orWhere('product_subcategory', 'like', "%{$category}%")
                       ->orWhere('mmc_method', 'like', "%{$category}%");
                });
            })
            ->whereNotNull('lat')->whereNotNull('lng')
            ->get([
                'id','name','lat','lng',
                'mmc_method','product_category','product_subcategory',
                'address','county_name','county_code',
                'phone','email','website','country','is_active',
            ]);

        return $rows->map(fn ($m) => [
            'id'                 => $m->id,
            'name'               => $m->name,
            'lat'                => (float) $m->lat,
            'lng'                => (float) $m->lng,
            'mmc_method'         => $m->mmc_method,
            'product_category'   => $m->product_category,
            'product_subcategory'=> $m->product_subcategory,
            'address'            => $m->address,
            'county_name'        => $m->county_name,
            'county_code'        => $m->county_code,
            'phone'              => $m->phone,
            'email'              => $m->email,
            'website'            => $m->website,
            'country'            => $m->country,
        ])->values()->all();
    }

    /** Push fresh points to the browser */
    protected function pushUpdate(): void
    {
        $this->dispatch('manufacturers-map:update', $this->queryPoints());
    }

    public function mount(): void
    {
        // initial push so the map draws after first render
        $this->pushUpdate();
    }

    /** Whenever a filter changes, refresh the markers */
    public function updated($field): void
    {
        $this->pushUpdate();
    }

    public function render()
    {
        // also pass points for the first paint (Blade @json)
        $points = $this->queryPoints();

        return view('livewire.admin.manufacturers-map', compact('points'));
    }
}
