<?php

namespace App\Livewire\Dashboard;

use App\Models\Prediction;
use Livewire\Attributes\On;
use Livewire\Component;

class PredictedTable extends Component
{
    public ?int $cityId = null;
    public ?int $year = null;
    public ?int $locationId = null;
    public array $max = [];

    private array $fields = [
        'target' => 'Water Depth (Target)',
        'rf' => 'RF', 'rbf' => 'RBF', 'dt' => 'DT', 'ann' => 'ANN',
        'rnn' => 'RNN', 'lstm' => 'LSTM', 'gru' => 'GRU',
    ];

    #[On('filters.changed')]
    public function updateFilters($cityId, $year, $locationId): void
    {
        $this->cityId = $cityId;
        $this->year = $year;
        $this->locationId = $locationId;
        $this->compute();
    }

    public function mount(): void
    {
        $this->compute();
    }

    private function compute(): void
    {
        $q = Prediction::query()
            ->when($this->locationId, fn($b) => $b->where('location_id', $this->locationId))
            ->when($this->cityId, fn($b) => $b->whereHas('location', fn($c) => $c->where('city_id', $this->cityId)))
            ->when($this->year, fn($b) => $b->where('year', $this->year));

        $out = [];
        foreach ($this->fields as $key => $label) {
            $out[$label] = (clone $q)->max($key);
        }
        $this->max = $out;
    }

    public function render()
    {
        return view('livewire.dashboard.predicted-table');
    }
}
