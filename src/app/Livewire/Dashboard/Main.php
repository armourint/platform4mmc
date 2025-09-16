<?php
namespace App\Livewire\Dashboard;
use Livewire\Component;
use App\Models\ModelRun;
use Illuminate\Support\Carbon;
class Main extends Component {
    public ?int $runId=null; 
    public ?string $location=null; 
    public int $hour=0; 
    public string $model='target_depth_m';

    public function mount(){ $this->runId = ModelRun::latest('id')->value('id'); }

    public function render(){
        $run = $this->runId ? ModelRun::find($this->runId) : null;

        $locations = $run ? $run->observations()
            ->select('location')->distinct()->pluck('location') : collect();

        $from = $run?->observations()->min('observed_at'); 
        $to   = $run?->observations()->max('observed_at');
        $ts = $from ? Carbon::parse($from)->addHours($this->hour) : null;

        // Points for map
        $points = $run ? $run->observations()
            ->when($this->location, fn($q)=>$q->where('location',$this->location))
            ->get(['location','inputs','outputs'])
            ->map(function($o){ 
                return [
                    'lat' => (float)($o->inputs['latitude'] ?? 0),
                    'lng' => (float)($o->inputs['longitude'] ?? 0),
                    'loc' => $o->location,
                    'val' => $o->outputs[$this->model] ?? null,
                ];
            }) : collect();

        // Time-series for selected location (all models to compare)
        $series = [];
        if ($run && $this->location) {
            $rows = $run->observations()
                ->where('location',$this->location)
                ->orderBy('observed_at')
                ->get(['observed_at','outputs']);
            $models = ['target_depth_m','rf_depth_m','rbf_depth_m','rnn_depth_m','dt_depth_m','ann_depth_m','lstm_depth_m','gru_depth_m'];
            foreach ($models as $m) {
                $series[$m] = $rows->map(function($r) use ($m){
                    $v = $r->outputs[$m] ?? null;
                    return ['t' => optional($r->observed_at)->toIso8601String(), 'v' => $v];
                })->all();
            }
        }

        // Max predicted depth per location (for selected model)
        $maxTable = [];
        if ($run) {
            $grouped = $run->observations()->get(['location','outputs'])->groupBy('location');
            foreach ($grouped as $loc => $items) {
                $max = null;
                foreach ($items as $it) {
                    $val = $it->outputs[$this->model] ?? null;
                    if ($val !== null) $max = is_null($max) ? $val : max($max, $val);
                }
                $maxTable[] = ['location'=>$loc, 'max'=>$max];
            }
            usort($maxTable, function($a,$b){ return ($b['max'] <=> $a['max']); });
        }

        return view('livewire.dashboard.main', compact('run','locations','from','to','points','series','maxTable'));
    }
}
