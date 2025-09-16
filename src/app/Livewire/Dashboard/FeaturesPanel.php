<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class FeaturesPanel extends Component
{
    public array $rows = [
        ['feature'=>'Pressure','max'=>'101,201', 'units'=>'Pa'],
        ['feature'=>'Humidity','max'=>'0.9433', 'units'=>'%'],
        ['feature'=>'SoilMoisture','max'=>'0.2863', 'units'=>'kg/m²'],
        ['feature'=>'Temperature','max'=>'285.0935', 'units'=>'K'],
        ['feature'=>'Temperature2','max'=>'286.5232', 'units'=>'K'],
        ['feature'=>'Precipitation','max'=>'0.0000', 'units'=>'kg/m²'],
        ['feature'=>'Wind','max'=>'11.4399', 'units'=>'m/s'],
        ['feature'=>'RiverDischarge','max'=>'13.3434', 'units'=>'m³/s'],
        ['feature'=>'Tide','max'=>'2.3100', 'units'=>'m'],
    ];

    public function render() { return view('livewire.dashboard.features-panel'); }
}
