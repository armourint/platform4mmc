<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalFactor;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEnvA4FromExcel extends Command
{
    protected $signature = 'mmc:import-env-a4
        {--path= : Path to ireland_generic_mmc_model.xlsx}
        {--sheet="Wall Systems (A4)" : Sheet name}
        {--dataset-version=v2025.09 : Dataset version label}';

    protected $description = 'Import A4 transport stage (kgCO₂e/m²) and attach to EnvironmentalFactor per system_code.';

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('ireland_generic_mmc_model.xlsx');
        $sheetName = $this->option('sheet');
        $version = $this->option('dataset-version');

        if (!is_file($path)) { $this->error("File not found: $path"); return self::FAILURE; }

        $ds = DatasetVersion::firstOrCreate(
            ['module'=>'environmental','version_label'=>$version],
            ['status'=>'draft','payload'=>[]]
        );

        $ss = IOFactory::load($path);
        $sheet = $ss->getSheetByName($sheetName);
        if (!$sheet) { $this->error("Sheet not found: {$sheetName}"); return self::FAILURE; }

        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 2) { $this->warn('No data rows found.'); return self::SUCCESS; }

        $header = array_map(fn($h)=>$this->norm((string)$h), array_values($rows[1]));
        $idx = fn(array $aliases) => $this->findIndex($header, $aliases);

        $col = [
            'mmc_method'  => $idx(['mmc method']),
            'a4_per_m2'   => $idx(['a4 (kgco₂e/m²)','a4 (kgco2e/m2)']),
        ];

        // if A4 rows exist for multiple assemblies of same system_code, average them
        $valsByCode = []; // code => [values...]

        for ($r=2; $r<=count($rows); $r++) {
            $row = array_values($rows[$r] ?? []);
            if (!$row) continue;

            $mmcMethod = strtoupper($this->val($row, $col['mmc_method']));
            $code = $this->deriveCode($mmcMethod);
            $a4 = $this->float($row, $col['a4_per_m2']);

            if ($code && $a4 !== null) {
                $valsByCode[$code] = $valsByCode[$code] ?? [];
                $valsByCode[$code][] = $a4;
            }
        }

        $updated = 0;
        foreach ($valsByCode as $code => $arr) {
            $avg = array_sum($arr) / max(count($arr), 1);
            EnvironmentalFactor::updateOrCreate(
                ['dataset_version_id'=>$ds->id, 'system_code'=>$code],
                ['a4_per_m2' => round($avg, 6)]
            );
            $updated++;
        }

        $this->info("Updated A4 per m² for {$updated} system code(s) in dataset {$ds->version_label} (ID {$ds->id}).");
        return self::SUCCESS;
    }

    /* helpers */
    private function norm(string $v): string { $v=trim(mb_strtolower($v)); $v=str_replace(['–','—'],'-',$v); return preg_replace('/\s+/', ' ', $v); }
    private function findIndex(array $header, array $aliases): ?int { $aliases=array_map(fn($a)=>$this->norm($a),$aliases); foreach($header as $i=>$h){ if(in_array($this->norm($h),$aliases,true)) return $i; } return null; }
    private function val(array $row, ?int $i): ?string { if($i===null) return null; $v=$row[$i]??null; return is_string($v)?trim($v):(is_null($v)?null:(string)$v); }
    private function float(array $row, ?int $i): ?float { if($i===null) return null; $v=$row[$i]??null; if($v===''||$v===null) return null; $v=is_string($v)?str_replace([','],['.'],$v):$v; return is_numeric($v)?(float)$v:null; }
    private function deriveCode(?string $method): ?string { if(!$method) return null; $m=strtoupper(trim($method)); if(str_contains($m,'LIGHT GAUGE STEEL')||str_contains($m,'LGS')) return 'LGS'; if(str_contains($m,'INSULATED CONCRETE')||str_contains($m,'ICF')) return 'ICF'; if(str_contains($m,'TIMBER')) return 'TF'; if(str_contains($m,'BLOCK')) return 'BLOCK'; return preg_replace('/[^A-Z0-9]/','',$m)?:null; }
}