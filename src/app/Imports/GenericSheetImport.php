<?php
namespace App\Imports;
use App\Models\ModelRun;
use App\Models\ModelObservation;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class GenericSheetImport implements ToCollection
{
    public function __construct(protected ModelRun $run, protected array $mapping){}
    public function collection(Collection $rows){
        if ($rows->isEmpty()) return;
        $headers = $rows->first()->map(fn($v)=> trim((string)$v));
        foreach ($rows->slice(1) as $row) {
            $rowAssoc = [];
            foreach ($headers as $i=>$key) { $rowAssoc[$key] = $row[$i] ?? null; }
            $observedAt = Arr::get($rowAssoc, $this->mapping['observed_at'] ?? '');
            $observedAt = $observedAt ? Carbon::parse($observedAt) : null;
            $location = Arr::get($rowAssoc, $this->mapping['location'] ?? '');
            $inputs = collect($this->mapping['inputs'] ?? [])->mapWithKeys(fn($src,$dest)=>[$dest => $rowAssoc[$src] ?? null])->toArray();
            $outputs = collect($this->mapping['outputs'] ?? [])->mapWithKeys(function($src,$dest){
                $val = $src !== null ? ($this->tryFloat($src)) : null;
                return [$dest => $val];
            })->map(function($v,$k) use ($rowAssoc){
                $srcCol = $this->mapping['outputs'][$k] ?? null;
                $raw = $srcCol ? ($rowAssoc[$srcCol] ?? null) : null;
                $num = is_numeric($raw) ? (float)$raw : null;
                if (str_contains($k,'depth') && $num !== null && $num < 0) $num = 0.0;
                return $num;
            })->toArray();
            ModelObservation::create([
                'model_run_id'=>$this->run->id,
                'observed_at'=>$observedAt,
                'location'=>$location,
                'inputs'=>$inputs,
                'outputs'=>$outputs ?: null,
            ]);
        }
    }
}
