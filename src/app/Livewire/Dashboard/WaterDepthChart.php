<?php

namespace App\Livewire\Dashboard;

use App\Models\City;
use App\Models\Prediction;
use Livewire\Attributes\On;
use Livewire\Component;

class WaterDepthChart extends Component
{
    public ?int $cityId = null;
    public ?int $year = null;
    public ?int $locationId = null;

    public array $series = [];

    #[On('filters.changed')]
    public function updateFilters($cityId, $year, $locationId): void
    {
        $this->cityId = $cityId ?: $this->cityId;
        $this->year = $year ?: $this->year;
        $this->locationId = $locationId;
        $this->loadData();
    }

    public function mount(): void
    {
        // Fallback defaults if nothing has been selected/emitted yet
        $this->cityId ??= City::query()->value('id');
        $this->year   ??= Prediction::query()->select('year')->distinct()->orderByDesc('year')->value('year');

        $this->loadData();
    }

    private function loadData(): void
    {
        if (!$this->year || !$this->cityId) { $this->series = []; return; }

        $q = Prediction::query()
        ->where('year', $this->year)
        ->whereHas('location', fn($c) => $c->where('city_id', $this->cityId));

        // 🔸 require a location id — if null, pick first for city
        if (!$this->locationId) {
            $this->locationId = \App\Models\Location::where('city_id', $this->cityId)->orderBy('code')->value('id');
        }
        $q->where('location_id', $this->locationId);


        $rows = $q->get(['at','target','rf','rbf','dt','ann','rnn','lstm','gru']);

        // Build labels/series
        $labels = $rows->pluck('at')->map->format('Y-m-d H:i');
        $fields = ['target','rf','rbf','dt','ann','rnn','lstm','gru'];
        $series = ['labels' => $labels];

        // Collect all numeric values to compute y-axis
        $all = [];
        foreach ($fields as $f) {
            $vals = $rows->pluck($f)->map(fn($v)=> is_null($v)? null : (float)$v);
            $series[$f] = $vals;
            foreach ($vals as $v) if ($v !== null) $all[] = $v;
        }

        if (count($all)) {
            $min = min($all);
            $max = max($all);
            $pad = max(0.1, ($max - $min) * 0.1); // 10% padding, at least 0.1m
            $series['y'] = [
                'min' => floor(($min - $pad) * 10) / 10,
                'max' => ceil(($max + $pad) * 10) / 10,
            ];
        } else {
            $series['y'] = ['min' => 0, 'max' => 1];
        }

        $this->series = $series;
    }

    public function render()
    {
        return view('livewire.dashboard.water-depth-chart');
    }
}
