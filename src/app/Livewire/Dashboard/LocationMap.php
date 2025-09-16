<?php

namespace App\Livewire\Dashboard;

use App\Models\Location;
use App\Models\Prediction;
use Livewire\Attributes\On;
use Livewire\Component;

class LocationMap extends Component
{
    public ?int $cityId = null;
    public ?int $year   = null;

    public array $locations = []; // each: id, code, lat, lng, i, j, max_depth, color

    #[On('filters.changed')]
    public function updateFilters($cityId, $year, $locationId): void
    {
        $this->cityId = $cityId;
        $this->year   = $year;
        $this->loadLocations();
    }

    public function mount(): void
    {
        $this->loadLocations();
    }

    private function loadLocations(): void
    {
        if (!$this->cityId) { $this->locations = []; return; }

        // Fetch base locations
        $locs = Location::query()
            ->where('city_id', $this->cityId)
            ->whereNotNull('lat')->whereNotNull('lng')
            ->orderBy('code')
            ->get(['id','code','lat','lng','i','j']);

        // For the selected year, compute the max predicted depth across all models per location
        $stats = Prediction::query()
            ->whereHas('location', fn($q) => $q->where('city_id', $this->cityId))
            ->when($this->year, fn($q) => $q->where('year', $this->year))
            ->selectRaw("
                location_id,
                MAX(GREATEST(
                  COALESCE(target,0), COALESCE(rf,0), COALESCE(rbf,0), COALESCE(dt,0),
                  COALESCE(ann,0), COALESCE(rnn,0), COALESCE(lstm,0), COALESCE(gru,0)
                )) AS max_depth
            ")
            ->groupBy('location_id')
            ->pluck('max_depth','location_id');

        // Attach risk color by thresholds (<0.5 green, <0.75 amber, >=0.75 red)
        $this->locations = $locs->map(function ($l) use ($stats) {
            $d = (float) ($stats[$l->id] ?? 0);
            $color = $d >= 0.75 ? '#EF4444' : ($d >= 0.5 ? '#F59E0B' : '#22C55E'); // red / amber / green
            return [
                'id' => $l->id,
                'code' => $l->code,
                'lat' => (float)$l->lat,
                'lng' => (float)$l->lng,
                'i' => $l->i, 'j' => $l->j,
                'max_depth' => $d,
                'color' => $color,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.location-map');
    }
}
