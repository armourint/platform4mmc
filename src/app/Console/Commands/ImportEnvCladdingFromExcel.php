<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalLayer;
use App\Models\EnvironmentalFactor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEnvCladdingFromExcel extends Command
{
    protected $signature = 'mmc:import-env-cladding
        {--path= : Path to ireland_generic_mmc_model.xlsx}
        {--sheet="Cladding Systems" : Sheet name}
        {--dataset-version=v2025.09 : Dataset version label}';

    protected $description = 'Import cladding systems (no geometry required). Computes A1–A3/m² if possible.';

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

        $header = array_map(fn($h) => $this->norm((string)$h), array_values($rows[1]));
        $idx = fn(array $aliases) => $this->findIndex($header, $aliases);

        $col = [
            'assembly_id'      => $idx(['system id','assembly id']),
            'system_name'      => $idx(['system name']),
            'mmc_method'       => $idx(['mmc method']),
            'source_header'    => $idx(['source header']),
            'layer_no'         => $idx(['layer no','layer number', 'layer no.']),
            'functional_role'  => $idx(['functional role','function','role']),
            'generic_material' => $idx(['generic material','material']),
            'thickness_m'      => $idx(['thickness (m)','thickness m']),
            'total_volume_m3'  => $idx(['total volume (m3)','total volume']),
            'density_kg_m3'    => $idx(['density (kg.m3)','density (kg/m3)','density']),
            'mass_kg_m2'       => $idx(['mass (kg/m²)','mass (kg/m2)','mass kg/m2']),
            'carbon_factor'    => $idx(['carbon factor','gwp factor']),
            'total_gwp'        => $idx(['total gwp (kgco₂e)','total gwp (kgco2e)']),
        ];

        $importedLayers = 0;
        $sumByCode = [];

        DB::transaction(function () use ($rows, $col, $ds, &$importedLayers, &$sumByCode) {
            for ($r=2; $r<=count($rows); $r++) {
                $row = array_values($rows[$r] ?? []);
                if (!$row) continue;

                $assemblyId = $this->val($row, $col['assembly_id']);
                $systemName = $this->val($row, $col['system_name']);
                if (!$assemblyId && !$systemName) continue;

                $mmcMethod  = strtoupper($this->val($row, $col['mmc_method']));
                $systemCode = $this->deriveCode($mmcMethod);

                EnvironmentalLayer::updateOrCreate(
                    [
                        'dataset_version_id'=>$ds->id,
                        'system_code'=>$systemCode,
                        'assembly_id'=>$assemblyId,
                        'layer_no'=>$this->intval($row, $col['layer_no']),
                    ],
                    [
                        'system_name'=>$systemName,
                        'source_header'=>$this->val($row, $col['source_header']),
                        'functional_role'=>$this->val($row, $col['functional_role']),
                        'generic_material'=>$this->val($row, $col['generic_material']),
                        'thickness_m'=>$this->float($row, $col['thickness_m']),
                        'total_volume_m3'=>$this->float($row, $col['total_volume_m3']),
                        'density_kg_m3'=>$this->float($row, $col['density_kg_m3']),
                        'mass_kg_m2'=>$this->float($row, $col['mass_kg_m2']),
                        'carbon_factor'=>$this->float($row, $col['carbon_factor']),
                    ]
                );
                $importedLayers++;

                $perM2 = null;
                $mass = $this->float($row, $col['mass_kg_m2']);
                $cf   = $this->float($row, $col['carbon_factor']);
                if ($mass !== null && $cf !== null) $perM2 = $mass * $cf;

                if ($perM2 !== null && $systemCode) {
                    $sumByCode[$systemCode] = ($sumByCode[$systemCode] ?? 0) + $perM2;
                }
            }

            foreach ($sumByCode as $code => $sumPerM2) {
                EnvironmentalFactor::updateOrCreate(
                    ['dataset_version_id'=>$ds->id, 'system_code'=>$code],
                    ['a1_a3_per_m2'=>round($sumPerM2,6)]
                );
            }
        });

        $this->info("Imported {$importedLayers} cladding layer rows; updated ".count($sumByCode)." system A1–A3/m² entries (dataset {$ds->version_label}).");
        return self::SUCCESS;
    }

    /* helpers */
    private function norm(string $v): string { $v=trim(mb_strtolower($v)); $v=str_replace(['–','—'],'-',$v); return preg_replace('/\s+/', ' ', $v); }
    private function findIndex(array $header, array $aliases): ?int { $aliases=array_map(fn($a)=>$this->norm($a),$aliases); foreach($header as $i=>$h){ if(in_array($this->norm($h),$aliases,true)) return $i; } return null; }
    private function val(array $row, ?int $i): ?string { if ($i===null) return null; $v=$row[$i]??null; return is_string($v)?trim($v):(is_null($v)?null:(string)$v); }
    private function float(array $row, ?int $i): ?float { if ($i===null) return null; $v=$row[$i]??null; if($v===''||$v===null) return null; $v=is_string($v)?str_replace([','],['.'],$v):$v; return is_numeric($v)?(float)$v:null; }
    private function intval(array $row, ?int $i): ?int { $f=$this->float($row,$i); return $f===null?null:(int)$f; }
    private function deriveCode(?string $method): ?string { if(!$method) return null; $m=strtoupper(trim($method)); if(str_contains($m,'LIGHT GAUGE STEEL')||str_contains($m,'LGS')) return 'LGS'; if(str_contains($m,'INSULATED CONCRETE')||str_contains($m,'ICF')) return 'ICF'; if(str_contains($m,'TIMBER')) return 'TF'; if(str_contains($m,'BLOCK')) return 'BLOCK'; return preg_replace('/[^A-Z0-9]/','',$m)?:null; }
}