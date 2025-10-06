<?php

namespace App\Console\Commands;

use App\Models\DataImport;
use App\Models\DatasetVersion;
use App\Models\EnvironmentalFactor;
use App\Models\EnvironmentalLayer;
use App\Models\EnvironmentalProperty;
use App\Models\EnvironmentalSnapshot;
use App\Models\EnvironmentalSystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEnvironmentalLayersFromExcel extends Command
{
    protected $signature = 'mmc:import-environmental-layers
        {--import-id= : Use a DataImport row (takes precedence over other options)}
        {--path= : Absolute path to the Excel workbook}
        {--dataset-version= : DatasetVersion ID or label to attach to}
        {--dataset-version-id= : Reuse this dataset_versions.id (never create another)}
        {--reset : Truncate existing rows for this dataset_version before import}
        {--sheet= : Force a specific sheet name (debug)}
        {--a4-factor=0.06 : Default A4 factor in kgCO2e per kg of transported mass}
        {--u-default=0.18 : Default U-value (W/m²K) when not present}
        {--snapshot : Build per-system snapshots after import}';

    protected $description = 'Import Environmental layers (A1–A3) from MMC workbook; upsert systems, seed U & A4, and optionally snapshot.';

    // Candidate sheet names per category (order = priority)
    protected array $SHEETS_WALLS    = ['Wall Systems', 'Walls', 'A1-A3 Walls', 'Walls (A1-A3)'];
    protected array $SHEETS_CLADDING = ['Cladding Systems', 'Cladding', 'A1-A3 Cladding', 'Cladding (A1-A3)'];
    protected array $SHEETS_SLABS    = ['Slab Systems', 'Slabs', 'A1-A3 Slabs', 'Slabs (A1-A3)'];

    // Column alias map (left = logical field, right = array of fuzzy matches)
    protected array $ALIASES = [
        // System meta
        'system_code'      => ['System Code','SystemCode','Code','MMC Code'],
        'mmc_method'       => ['MMC Method','MMCMethod','Method','MMC Category'],
        'assembly_id'      => ['Assembly ID','System ID','SystemID','ID'],
        'system_name'      => ['System Name','SystemName','Name'],
        'system_category'  => ['System Category','SystemCategory','System Type','SystemType'],

        // Layer fields
        'layer_no'         => ['Layer No.','LayerNo','Layer Number','Layer #','Layer'],
        'functional_role'  => ['Functional Role','Role','Function'],
        'generic_material' => ['Generic Material','Material','Generic Material Name'],

        // Geometry / quantities
        'length_m'         => ['Length (m)','Length_m','Length m'],
        'height_m'         => ['Height (m)','Height_m','Height m'],
        'thickness_m'      => ['Thickness (m)','Thickness_m','Thickness m','Thickness'],
        'thickness_mm'     => ['Thickness (mm)','Thickness_mm','Thickness mm'],

        'element_volume_m3'=> ['Element Volume (m3)','Element_Volume_m3','Elem Vol (m3)'],
        'element_number'   => ['Element Number','Element_Number','Qty','Quantity'],
        'total_volume_m3'  => ['Total Volume (m3)','Total_Volume_m3','Total Vol (m3)'],

        // Physical props
        'density_kg_m3'    => ['Density (kg/m3)','Density_kg_m3','Density kg/m3','Density (kg/m³)'],
        'mass_kg_m2'       => ['Mass (kg/m²)','Mass kg/m2','Mass kg/m^2','Mass (kg/m2)','Mass (kg/m^2)'],

        // Carbon
        'carbon_factor'    => ['Carbon Factor (kgCO2e/kg)','Carbon Factor','Carbon factor (kgCO2e/kg)','CF (kgCO2e/kg)'],
        'a1a3_per_m2'      => ['A1–A3 (kg CO2e / m²)','A1-A3 (kgCO2e/m2)','A1A3_kgCO2e_per_m2','A1-A3 kgCO2e/m²'],
        'a1a3_per_5_76_m2' => ['A1–A3 (kg CO2e / 5.76 m²)','A1-A3 (kgCO2e / 5.76 m2)','A1A3_kgCO2e_per_5_76m2'],
    ];

    public function handle(): int
    {
        try {
            [$path, $datasetVersionId, $sourceFrom] = $this->resolveSourceAndDatasetVersion();
            $this->line("Source: {$sourceFrom}");
            $this->info("Using dataset_version_id = {$datasetVersionId}");
            $this->line("Workbook: {$path}");

            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $wb = $reader->load($path);

            if ($this->option('reset')) {
                DB::transaction(function () use ($datasetVersionId) {
                    DB::table('environmental_layers')->where('dataset_version_id', $datasetVersionId)->delete();
                    DB::table('environmental_systems')->where('dataset_version_id', $datasetVersionId)->delete();
                    DB::table('environmental_factors')->where('dataset_version_id', $datasetVersionId)->delete();
                    DB::table('environmental_snapshots')->where('dataset_version_id', $datasetVersionId)->delete();
                });
                $this->warn("Reset: deleted existing rows for dataset_version_id={$datasetVersionId}");
            }

            $forced = $this->option('sheet');
            $total = 0;

            if ($forced) {
                $total += $this->importCategory($wb, $datasetVersionId, [$forced], 'Unknown', $forced);
            } else {
                $total += $this->importCategory($wb, $datasetVersionId, $this->SHEETS_WALLS, 'Wall', 'A1-A3 Walls');
                $total += $this->importCategory($wb, $datasetVersionId, $this->SHEETS_CLADDING, 'Cladding', 'A1-A3 Cladding');
                $total += $this->importCategory($wb, $datasetVersionId, $this->SHEETS_SLABS, 'Slab', 'A1-A3 Slabs');
            }

            $this->info("Imported {$total} environmental layer rows.");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");
            if ($this->getOutput()->isVerbose()) {
                $this->line($e->getTraceAsString());
            }
            return self::FAILURE;
        }
    }

    // ---------------------------------------------------------------------
    // Core import per category
    // ---------------------------------------------------------------------
    protected function importCategory(\PhpOffice\PhpSpreadsheet\Spreadsheet $wb, int $datasetVersionId, array $sheetCandidates, string $systemCategory, string $sourceHeader): int
    {
        $sheetName = $this->pickSheet($wb, $sheetCandidates);
        if (!$sheetName) {
            $this->warn("No sheet found for {$systemCategory} (candidates: ".implode(', ', $sheetCandidates).')');
            return 0;
        }

        $ws   = $wb->getSheetByName($sheetName);
        $rows = $ws->toArray(null, true, true, true);
        if (!$rows) return 0;

        // Find header
        $headerRow = null; $start = 0;
        foreach ($rows as $i => $r) {
            if (array_filter($r, fn($v) => $v !== null && $v !== '')) {
                $headerRow = array_map(fn($x) => $x === null ? '' : trim((string)$x), array_values($r));
                $start = $i + 1;
                break;
            }
        }
        if (!$headerRow) return 0;

        $hdr = $headerRow;
        $col = function(string $logical) use ($hdr) {
            $targets = $this->ALIASES[$logical] ?? [$logical];
            return $this->findHeader($hdr, $targets);
        };

        // Resolve column indices once
        $c_system_code      = $col('system_code');
        $c_mmc_method       = $col('mmc_method');
        $c_assembly_id      = $col('assembly_id');
        $c_system_name      = $col('system_name');
        $c_system_category  = $col('system_category');

        $c_layer_no         = $col('layer_no');
        $c_role             = $col('functional_role');
        $c_mat              = $col('generic_material');

        $c_len              = $col('length_m');
        $c_hgt              = $col('height_m');
        $c_thick_m          = $col('thickness_m');
        $c_thick_mm         = $col('thickness_mm');
        $c_elem_vol         = $col('element_volume_m3');
        $c_elem_num         = $col('element_number');
        $c_tot_vol          = $col('total_volume_m3');

        $c_density          = $col('density_kg_m3');
        $c_mass_m2          = $col('mass_kg_m2');

        $c_cf               = $col('carbon_factor');
        $c_a1a3_m2          = $col('a1a3_per_m2');
        $c_a1a3_576         = $col('a1a3_per_5_76_m2');

        $insert  = [];
        $count   = 0;

        // Per-system caches
        $systemsTouched = []; // [code] => meta
        $agg = [];            // [code] => mass_total, a1a3_total, layers[]

        // Row loop
        for ($i = $start; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null; if (!$r) continue;
            $vals = array_values($r);
            if (!array_filter($vals, fn($v) => $v !== null && $v !== '')) continue;

            $get = function($idx) use ($vals) { return $idx === null ? null : ($vals[$idx] ?? null); };

            // System meta
            $system_code  = $this->cleanStr($get($c_system_code));
            $mmc_method   = $this->cleanStr($get($c_mmc_method));
            $assembly_id  = $this->cleanStr($get($c_assembly_id)) ?: null;
            $system_name  = $this->cleanStr($get($c_system_name)) ?: null;
            $sys_cat      = $systemCategory ?: $this->cleanStr($get($c_system_category));
            $sys_cat      = $this->normalizeCategory($sys_cat);
            if (!$system_code) $system_code = $this->deriveSystemCode($mmc_method);

            // Layer fields
            $layer_no     = $this->toInt($get($c_layer_no));
            $role         = $this->cleanStr($get($c_role));
            $material     = $this->cleanStr($get($c_mat));

            // Dimensions
            $length_m     = $this->toFloat($get($c_len));
            $height_m     = $this->toFloat($get($c_hgt));

            $thickness_m  = $this->toFloat($get($c_thick_m));
            if ($thickness_m === null && $c_thick_mm !== null) {
                $mm = $this->toFloat($get($c_thick_mm));
                if ($mm !== null) $thickness_m = $mm / 1000.0;
            }

            $elem_vol     = $this->toFloat($get($c_elem_vol));
            $elem_num     = $this->toInt($get($c_elem_num));
            $tot_vol      = $this->toFloat($get($c_tot_vol));

            // Physical props
            $density      = $this->toFloat($get($c_density));
            $mass_m2      = $this->toFloat($get($c_mass_m2));

            // Carbon
            $cf           = $this->toFloat($get($c_cf));
            $a1a3_m2      = $this->toFloat($get($c_a1a3_m2));
            $a1a3_576     = $this->toFloat($get($c_a1a3_576));

            if ($a1a3_m2 === null && $mass_m2 !== null && $cf !== null) {
                $a1a3_m2 = $mass_m2 * $cf;
            }

            // Ignore empty rows
            if ($role === null && $material === null) continue;

            // Prepare layer insert
            $insert[] = [
                'dataset_version_id' => $datasetVersionId,
                'system_code'        => $system_code ?: 'UNKNOWN',
                'mmc_method'         => $mmc_method ?: null,
                'assembly_id'        => $assembly_id,
                'system_name'        => $system_name,
                'system_category'    => $sys_cat,
                'source_header'      => $sourceHeader,

                'layer_no'           => $layer_no,
                'functional_role'    => $role,
                'generic_material'   => $material,

                'length_m'           => $length_m,
                'height_m'           => $height_m,
                'thickness_m'        => $thickness_m,
                'element_volume_m3'  => $elem_vol,
                'element_number'     => $elem_num,
                'total_volume_m3'    => $tot_vol,

                'density_kg_m3'      => $density,
                'mass_kg_m2'         => $mass_m2,

                'carbon_factor'      => $cf,
                'a1a3_per_5_76_m2'   => $a1a3_576,
                'a1a3_per_m2'        => $a1a3_m2,

                'created_at'         => now(),
                'updated_at'         => now(),
            ];

            // Catalog (once per system per dataset)
            if (!isset($systemsTouched[$system_code])) {
                $systemsTouched[$system_code] = [
                    'dataset_version_id' => $datasetVersionId,
                    'system_code'        => $system_code,
                    'assembly_id'        => $assembly_id,
                    'system_name'        => $system_name ?: $system_code,
                    'system_category'    => $sys_cat,
                    'mmc_method'         => $mmc_method,
                    'is_active'          => true,
                    'slug'               => Str::slug($system_name ?: $system_code),
                ];
            }

            // Aggregates for snapshots & A4
            if (!isset($agg[$system_code])) {
                $agg[$system_code] = [
                    'mass_total' => 0.0,
                    'a1a3_total' => 0.0,
                    'layers'     => [],
                ];
            }
            $agg[$system_code]['mass_total'] += (float) ($mass_m2 ?? 0);
            $agg[$system_code]['a1a3_total'] += (float) ($a1a3_m2 ?? 0);
            $agg[$system_code]['layers'][] = [
                'layer_no'         => $layer_no,
                'functional_role'  => $role,
                'generic_material' => $material,
                'mass_kg_m2'       => $mass_m2,
                'a1a3_per_m2'      => $a1a3_m2,
                'carbon_factor'    => $cf,
            ];
        }

        // Bulk insert layers
        DB::transaction(function () use (&$count, $insert) {
            foreach (array_chunk($insert, 1000) as $chunk) {
                DB::table('environmental_layers')->insert($chunk);
                $count += count($chunk);
            }
        });

        // Upsert system catalog
        foreach ($systemsTouched as $code => $data) {
            EnvironmentalSystem::updateOrCreate(
                ['dataset_version_id' => $datasetVersionId, 'system_code' => $code],
                $data
            );
        }

        // Properties (U only for MVP / default)
        $uDefault = (float) $this->option('u-default') ?: 0.18;
        foreach (array_keys($systemsTouched) as $code) {
            $sys = EnvironmentalSystem::where([
                'dataset_version_id' => $datasetVersionId,
                'system_code'        => $code,
            ])->first();

            if ($sys && !$sys->properties) {
                EnvironmentalProperty::create([
                    'environmental_system_id' => $sys->id,
                    'u_value_w_m2k'           => $uDefault,
                ]);
            }
        }

        // A4 factors (formulaic)
        $a4PerKg = max(0.0, (float) $this->option('a4-factor'));
        foreach ($agg as $code => $a) {
            $a4 = $a4PerKg > 0 ? ($a['mass_total'] * $a4PerKg) : 0.0;
            EnvironmentalFactor::updateOrCreate(
                ['dataset_version_id' => $datasetVersionId, 'system_code' => $code],
                [
                    'a4_kgco2e_m2'    => round($a4, 6),
                    // leave a5/c1-c4 null for MVP
                    'source'          => 'importer-default',
                    'meta_json'       => null,
                ]
            );
        }

        // Snapshots (optional)
        if ($this->option('snapshot')) {
            foreach ($agg as $code => $a) {
                $layers = collect($a['layers'])->sortBy('layer_no')->values()->all();
                $hotspots = collect($layers)
                    ->map(fn($r) => [
                        'label' => $r['generic_material'] ?: ($r['functional_role'] ?: ('Layer '.$r['layer_no'])),
                        'a1a3'  => (float) ($r['a1a3_per_m2'] ?? 0),
                    ])
                    ->sortByDesc('a1a3')->take(5)->values()->all();

                $a4 = (float) (EnvironmentalFactor::where([
                    'dataset_version_id' => $datasetVersionId,
                    'system_code'        => $code,
                ])->value('a4_kgco2e_m2') ?? 0);

                $kpi = [
                    'layer_count'              => count($layers),
                    'mass_total_kg_m2'         => round((float)$a['mass_total'], 6),
                    'a1a3_total_kgco2e_m2'     => round((float)$a['a1a3_total'], 6),
                    'a4_total_kgco2e_m2'       => round($a4, 6),
                    'overall_total_kgco2e_m2'  => round((float)$a['a1a3_total'] + $a4, 6),
                ];

                $chart = collect($layers)->map(fn($r) => [
                    'x'          => (int) $r['layer_no'],
                    'mass_kg_m2' => (float) ($r['mass_kg_m2'] ?? 0),
                ])->values()->all();

                $checksum = md5(json_encode([$kpi, $layers]));

                EnvironmentalSnapshot::updateOrCreate(
                    ['dataset_version_id' => $datasetVersionId, 'system_code' => $code],
                    [
                        'kpi_json'        => $kpi,
                        'layers_json'     => $layers,
                        'hotspots_json'   => $hotspots,
                        'chart_rows_json' => $chart,
                        'checksum'        => $checksum,
                    ]
                );
            }
        }

        $this->info("  - {$systemCategory}: inserted {$count} rows from '{$sheetName}'");
        return $count;
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------
    protected function resolveSourceAndDatasetVersion(): array
    {
        // Prefer DataImport row
        if ($id = $this->option('import-id')) {
            /** @var DataImport $imp */
            $imp = DataImport::query()->findOrFail($id);

            $disk = $imp->disk ?: 'public';
            $path = \Storage::disk($disk)->path($imp->path);

            $datasetVersionId = $imp->dataset_version_id
                ?: $this->findOrCreateDatasetVersionId(
                    module: 'environmental',
                    label: data_get($imp->meta, 'dataset_label') ?: (string)$imp->id
                );

            if (!$imp->dataset_version_id) {
                $imp->dataset_version_id = $datasetVersionId;
                $imp->save();
            }

            return [$path, $datasetVersionId, "DataImport#{$imp->id} ({$imp->original_name})"];
        }

        // Manual path
        $path = (string) $this->option('path');
        if (!$path || !is_file($path)) {
            throw new \RuntimeException('--path is required (absolute file path) or use --import-id=');
        }

        // DatasetVersion by explicit id
        if ($this->option('dataset-version-id')) {
            $id = (int) $this->option('dataset-version-id');
            $exists = DatasetVersion::query()->whereKey($id)->exists();
            if (!$exists) throw new \RuntimeException("dataset_version_id={$id} not found.");
            return [$path, $id, 'manual path (id)'];
        }

        // DatasetVersion by label or create
        $labelOrId = (string) ($this->option('dataset-version') ?: now()->format('Ymd_His'));
        $datasetVersionId = $this->resolveDatasetVersionId('environmental', $labelOrId);

        return [$path, $datasetVersionId, 'manual path'];
    }

    protected function resolveDatasetVersionId(string $module, string $labelOrId): int
    {
        if (ctype_digit($labelOrId)) {
            $id = (int) $labelOrId;
            $exists = DatasetVersion::query()->whereKey($id)->exists();
            if ($exists) return $id;
        }
        return $this->findOrCreateDatasetVersionId($module, $labelOrId);
    }

    protected function findOrCreateDatasetVersionId(string $module, string $label): int
    {
        $dv = DatasetVersion::query()
            ->where('module', $module)
            ->where('version_label', $label)
            ->first();

        if ($dv) return (int) $dv->id;

        $dv = DatasetVersion::create([
            'module'        => $module,
            'version_label' => $label,
            'status'        => 'draft',
            'is_current'    => false,
        ]);

        return (int) $dv->id;
    }

    protected function pickSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $wb, array $candidates): ?string
    {
        $names = $wb->getSheetNames();
        foreach ($candidates as $cand) {
            foreach ($names as $n) {
                if (Str::of($n)->lower()->contains(Str::of($cand)->lower())) {
                    return $n;
                }
            }
        }
        foreach ($candidates as $cand) {
            if (in_array($cand, $names, true)) return $cand;
        }
        return null;
    }

    protected function findHeader(array $headerRow, array $aliases): ?int
    {
        $norm = fn($s) => strtolower(preg_replace('/\s+/', '', (string)$s));
        $targets = array_map($norm, $aliases);

        foreach ($headerRow as $idx => $label) {
            $h = $norm($label);
            foreach ($targets as $t) {
                if ($t !== '' && str_contains($h, $t)) return $idx;
            }
        }
        return null;
    }

    protected function normalizeCategory(?string $v): ?string
    {
        if ($v === null) return null;
        $s = strtolower(trim($v));
        if (str_contains($s, 'wall')) return 'Wall';
        if (str_contains($s, 'clad')) return 'Cladding';
        if (str_contains($s, 'slab')) return 'Slab';
        return ucfirst($s);
    }

    protected function deriveSystemCode(?string $mmcMethod): ?string
    {
        if (!$mmcMethod) return null;
        $s = strtolower($mmcMethod);
        return match (true) {
            str_contains($s, 'block')                                  => 'BLOCK',
            str_contains($s, 'lgs') || str_contains($s, 'light gauge') => 'LGS',
            str_contains($s, 'timber')                                 => 'TIMBER',
            str_contains($s, 'icf')                                    => 'ICF',
            default                                                     => strtoupper(Str::slug($mmcMethod, '_')),
        };
    }

    protected function cleanStr($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    protected function toFloat($v): ?float
    {
        if ($v === null) return null;
        if (is_numeric($v)) return (float)$v;
        $s = trim((string)$v);
        if ($s === '') return null;

        // 1.000,25 -> 1000.25
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $s)) {
            return (float) str_replace([ '.', ',' ], [ '', '.' ], $s);
        }
        // 1,000.25 or 1,000
        if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
            return (float) str_replace(',', '', $s);
        }
        // 1000,25 -> 1000.25
        if (preg_match('/^\d+,\d+$/', $s)) {
            return (float) str_replace(',', '.', $s);
        }
        $s2 = preg_replace('/[^0-9eE\.\-\+]/', '', $s);
        return is_numeric($s2) ? (float)$s2 : null;
    }

    protected function toInt($v): ?int
    {
        $f = $this->toFloat($v);
        return $f === null ? null : (int) round($f);
    }
}
