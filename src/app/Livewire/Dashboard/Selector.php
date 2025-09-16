<?php

namespace App\Livewire\Dashboard;

use App\Models\City;
use App\Models\Location;
use App\Models\Prediction;
use Livewire\Attributes\On;
use Livewire\Component;

class Selector extends Component
{
    public int $cityId = 0;
    public int $year = 0;
    public ?int $locationId = null;
    public string $search = '';

    /** @var array<int> */
    public array $yearsOptions = [];
    /** @var array<int, array{id:int,code:string}> */
    public array $locationOptions = [];

    public function mount(): void
    {
        // Defaults
        $this->cityId = City::query()->value('id') ?? 0;

        $this->refreshYears();
        $this->year = $this->yearsOptions[0] ?? (int) now()->year;

        $this->refreshLocations();
        $this->locationId = $this->locationOptions[0]['id'] ?? null;
    }

    // 🔸 Livewire v3: runs after the component is hydrated on the frontend.
    public function booted(): void
    {
        $this->emitFilters();
    }

    #[On('map.select-location')]
    public function setLocation(int $locationId): void
    {
        $this->locationId = $locationId;
        $this->emitFilters();
    }

    public function updatedCityId(): void
    {
        $this->refreshYears();
        if (!in_array($this->year, $this->yearsOptions, true)) {
            $this->year = $this->yearsOptions[0] ?? $this->year;
        }

        $this->refreshLocations();
        if (!$this->locationOptions || !collect($this->locationOptions)->contains('id', $this->locationId)) {
            $this->locationId = $this->locationOptions[0]['id'] ?? null;
        }

        $this->emitFilters();
    }

    public function updatedYear(): void
    {
        $this->refreshLocations();
        if (!$this->locationOptions || !collect($this->locationOptions)->contains('id', $this->locationId)) {
            $this->locationId = $this->locationOptions[0]['id'] ?? null;
        }

        $this->emitFilters();
    }

    public function updatedLocationId(): void
    {
        $this->emitFilters();
    }

    private function emitFilters(): void
    {
        // Always concrete values
        $this->dispatch('filters.changed',
            cityId: $this->cityId,
            year: $this->year,
            locationId: $this->locationId
        );
    }

    private function refreshYears(): void
    {
        $this->yearsOptions = Prediction::query()
            ->whereHas('location', fn($q) => $q->where('city_id', $this->cityId))
            ->select('year')->distinct()->orderByDesc('year')
            ->pluck('year')->map(fn($y) => (int)$y)->all();
    }

    private function refreshLocations(): void
    {
        $this->locationOptions = Location::query()
            ->where('city_id', $this->cityId)
            ->orderBy('code')
            ->get(['id','code'])
            ->map(fn($l) => ['id' => (int)$l->id, 'code' => $l->code])
            ->all();
    }

    public function render()
    {
        return view('livewire.dashboard.selector', [
            'cities' => City::orderBy('name')->get(),
            'yearsOptions' => $this->yearsOptions,
            'locationOptions' => $this->locationOptions,
        ]);
    }
}
