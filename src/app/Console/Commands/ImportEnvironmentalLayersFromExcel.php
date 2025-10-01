<?php

namespace App\Console\Commands;

use App\Models\DataImport;
use App\Models\DatasetVersion;
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
        {--sheet : Sheet name (default: na)}';

    protected $description = 'Import Environmental layers from the MMC workbook into environmental_layers (no schema changes)';

    // ------------ Sheet names ------------
    protected array $SHEETS_WALLS     = ['Wall Systems', 'Walls', 'A1-A3 Walls'];
    protected array $SHEETS_CLADDING  = ['Cladding Systems', 'Cladding', 'A1-A3 Cladding'];
    protected array $SHEETS_SLABS     = ['Slab Systems', 'Slabs', 'A1-A3 Slabs'];
    protected array $SHEET_A4_WALLS   = ['Wall Systems (A4)', 'Walls (A4)', 'A4 Walls', 'A4'];

    // ------------ Column alias map (left = logical name we use in code) ------------
    protected array $ALIASES = [
        // System meta
        'system_code'      => ['System Code','SystemCode','Code','MMC Code'],
        'mmc_method'       => ['MMC Method','MMCMethod','Method','MMC Category'],
        'assembly_id'      => ['Assembly ID','System ID','SystemID','ID'],
        'system_name'      => ['System Name','SystemName','Name'],
        'system_category'  => ['System Category','SystemCategory','System Type','SystemType'],

        // Layer fields
        'layer_no'         => ['Layer No.','LayerNo','Layer Number','Layer #'],
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
        'density_kg_m3'    => ['Density (kg/m3)','Density_kg_m3','Density kg/m3'],
        'mass_kg_m2'       => ['Mass (kg/m²)','Mass kg/m2','Mass kg/m^2','Mass (kg/m2)'],

        // Carbon
        'carbon_factor'    => ['Carbon Factor (kgCO2e/kg)','Carbon Factor','Carbon factor (kgCO2e/kg)'],
        'carbon_factor_unit'=> ['Carbon Factor Unit','CF Unit','CF Units','Carbon Factor (unit)'],
        'a1a3_per_m2'      => ['A1–A3 (kg CO2e / m²)','A1-A3 (kgCO2e/m2)','A1A3_kgCO2e_per_m2'],
        'a1a3_per_5_76_m2' => ['A1–A3 (kg CO2e / 5.76 m²)','A1-A3 (kgCO2e / 5.76 m2)','A1A3_kgCO2e_per_5_76m2'],

        // Thermal (per-layer)
        'thermal_conductivity_w_mk' => [
            'Thermal Conductivity (W/mK)','Thermal Conductivity (W/m·K)','Lambda (W/mK)','λ (W/mK)','k (W/mK)'
        ],
        'r_value_m2k_w'    => ['R-Value (m2K/W)','R Value (m²K/W)','R-Value','R (m2K/W)'],
        'u_value_w_m2k'    => ['U-Value (W/m2K)','U Value (W/m²·K)','U-value','U (W/m2K)'],

        // Durability
        'life_expectancy_years' => ['Life Expectancy (years)','Service Life (years)','Life (years)','Life span (years)'],

        // A4 (handled separately if you later map to environmental_factors)
        'a4_per_m2'        => ['A4 (kgCO2e/m2)','A4 (kgCO₂e/m²)','A4_kgCO2e_per_m2'],
    ];

    public function handle(): int
    {
        try {
            // 1) Resolve import source and dataset_version_id
            [$path, $datasetVersionId, $sourceFrom] = $this->resolveSourceAndDatasetVersion();
            $this->line("Source: {$sourceFrom}");
            $this->info("Using dataset_version_id = {$datasetVersionId}");
            $this->line("Workbook: {$path}");

            // 2) Load workbook (read-only)
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $wb = $reader->load($path);

            // 3) Reset (optional)
            if ($this->option('reset')) {
                DB::table('environmental_layers')->where('dataset_version_id', $datasetVersionId)->delete();
                $this->warn("Reset: deleted existing environmental_layers for dataset_version_id={$datasetVersionId}");
            }

            // 4) Import main A1–A3 sheets (walls, cladding, slabs)
            $total = 0;
            $total += $this->importCategory($wb, $datasetVersionId, $this->SHEETS_WALLS,    'Wall',     'A1-A3 Walls');
            $total += $this->importCategory($wb, $datasetVersionId, $this->SHEETS_CLADDING, 'Cladding', 'A1-A3 Cladding');
            $total += $this->importCategory($wb, $datasetVersionId, $this->SHEETS_SLABS,    'Slab',     'A1-A3 Slabs');

            $this->info("Imported {$total} layer rows.");

            // (Optional A4 factors can be added later.)
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");
            if ($this->getOutput()->isVerbose()) {
                $this->line($e->getTraceAsString());
            }
            return self::FAILURE;
        }
    }

    // ---------------------------- Core import per category ----------------------------

    protected function importCategory(\PhpOffice\PhpSpreadsheet\Spreadsheet $wb, int $datasetVersionId, array $sheetCandidates, string $systemCategory, string $sourceHeader): int
    {
        $sheetName = $this->pickSheet($wb, $sheetCandidates);
        if (!$sheetName) {
            $this->warn("No sheet found for {$systemCategory} (candidates: ".implode(', ', $sheetCandidates).')');
            return 0;
        }

        $ws = $wb->getSheetByName($sheetName);
        $rows = $ws->toArray(null, true, true, true);
        if (!$rows) return 0;

        // Find header row (first non-empty row)
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

        // Helper to find source column by alias
        $col = function(string $logical) use ($hdr) {
            $targets = $this->ALIASES[$logical] ?? [$logical];
            return $this->findHeader($hdr, $targets);
        };

        // Resolve columns once
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
        $c_cf_unit          = $col('carbon_factor_unit');
        $c_a1a3_m2          = $col('a1a3_per_m2');
        $c_a1a3_576         = $col('a1a3_per_5_76_m2');

        // NEW thermal/durability columns
        $c_lambda           = $col('thermal_conductivity_w_mk');
        $c_rvalue           = $col('r_value_m2k_w');
        $c_uvalue           = $col('u_value_w_m2k');
        $c_life             = $col('life_expectancy_years');

        $insert = [];
        for ($i = $start; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null; if (!$r) continue;
            $vals = array_values($r);
            if (!array_filter($vals, fn($v) => $v !== null && $v !== '')) continue;

            $get = function($idx) use ($vals) {
                return $idx === null ? null : ($vals[$idx] ?? null);
            };

            // Read raw values
            $system_code  = $this->cleanStr($get($c_system_code));
            $mmc_method   = $this->cleanStr($get($c_mmc_method));
            $assembly_id  = $this->cleanStr($get($c_assembly_id)) ?: null;
            $system_name  = $this->cleanStr($get($c_system_name)) ?: null;

            // Prefer given sheet category label, fallback to column if present
            $sys_cat      = $systemCategory ?: $this->cleanStr($get($c_system_category));
            $sys_cat      = $this->normalizeCategory($sys_cat);

            // If system_code missing, try deriving from mmc_method
            if (!$system_code) $system_code = $this->deriveSystemCode($mmc_method);

            // Layer fields
            $layer_no     = $this->toInt($get($c_layer_no));
            $role         = $this->cleanStr($get($c_role));
            $material     = $this->cleanStr($get($c_mat));

            // Dimensions / quantities
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
            $cf_unit      = $this->cleanStr($get($c_cf_unit));
            $a1a3_m2      = $this->toFloat($get($c_a1a3_m2));
            $a1a3_576     = $this->toFloat($get($c_a1a3_576));

            // If A1-A3 not given but mass & CF available, compute
            if ($a1a3_m2 === null && $mass_m2 !== null && $cf !== null) {
                $a1a3_m2 = $mass_m2 * $cf;
            }

            // Thermal / durability
            $lambda_w_mk  = $this->toFloat($get($c_lambda)); // thermal conductivity
            $r_value      = $this->toFloat($get($c_rvalue));
            $u_value      = $this->toFloat($get($c_uvalue));
            $life_years   = $this->toFloat($get($c_life));

            // Compute missing R from thickness / lambda if possible
            if ($r_value === null && $thickness_m !== null && $lambda_w_mk !== null && $lambda_w_mk > 0) {
                $r_value = $thickness_m / $lambda_w_mk;
            }
            // Compute missing U from R
            if ($u_value === null && $r_value !== null && $r_value > 0) {
                $u_value = 1.0 / $r_value;
            }

            // Ignore header/empty rows (require at least a material or role)
            if ($role === null && $material === null) continue;

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
                'carbon_factor_unit' => $cf_unit,
                'a1a3_per_5_76_m2'   => $a1a3_576,
                'a1a3_per_m2'        => $a1a3_m2,

                'thermal_conductivity_w_mk' => $lambda_w_mk,
                'r_value_m2k_w'             => $r_value,
                'u_value_w_m2k'             => $u_value,

                'life_expectancy_years'     => $life_years,

                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        // Bulk insert in chunks
        $count = 0;
        DB::transaction(function () use (&$count, $insert) {
            foreach (array_chunk($insert, 1000) as $chunk) {
                DB::table('environmental_layers')->insert($chunk);
                $count += count($chunk);
            }
        });

        $this->info("  - {$systemCategory}: inserted {$count} rows from sheet '{$sheetName}'");
        return $count;
    }

    // ---------------------------- Helpers ----------------------------

    protected function resolveSourceAndDatasetVersion(): array
    {
        // If an Admin Import queued this, prefer DataImport info
        if ($id = $this->option('import-id')) {
            /** @var DataImport $imp */
            $imp = DataImport::query()->findOrFail($id);

            $disk = $imp->disk ?: 'public';
            $path = \Storage::disk($disk)->path($imp->path);

            // DatasetVersion: use the one already associated, else create one from label in meta if present
            $datasetVersionId = $imp->dataset_version_id
                ?: $this->findOrCreateDatasetVersionId(
                    module: 'environmental',
                    label: data_get($imp->meta, 'dataset_label') ?: (string)$imp->id
                );

            // Backfill onto the DataImport row for consistency
            if (!$imp->dataset_version_id) {
                $imp->dataset_version_id = $datasetVersionId;
                $imp->save();
            }

            return [$path, $datasetVersionId, "DataImport#{$imp->id} ({$imp->original_name})"];
        }

        // Manual path
        $path = (string) $this->option('path');
        if (!$path || !is_file($path)) {
            throw new \RuntimeException('--path is required (absolute path to workbook) or use --import-id=');
        }

        // DatasetVersion selection
        if ($this->option('dataset-version-id')) {
            $id = (int) $this->option('dataset-version-id');
            $exists = DatasetVersion::query()->whereKey($id)->exists();
            if (!$exists) throw new \RuntimeException("dataset_version_id={$id} not found.");
            return [$path, $id, 'manual path'];
        }

        $labelOrId = (string) $this->option('dataset-version');
        if (!$labelOrId) {
            // Sensible default label: timestamp
            $labelOrId = now()->format('Ymd_His');
        }

        // If numeric and exists as ID, use it; else find/create by label under module='environmental'
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
            'status'        => 'draft',   // do not auto-publish
            'is_current'    => false,
        ]);

        return (int) $dv->id;
    }

    protected function pickSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $wb, array $candidates): ?string
    {
        $names = $wb->getSheetNames();
        // Search by case-insensitive contains
        foreach ($candidates as $cand) {
            foreach ($names as $n) {
                if (Str::of($n)->lower()->contains(Str::of($cand)->lower())) {
                    return $n;
                }
            }
        }
        // Exact match fallback
        foreach ($candidates as $cand) {
            if (in_array($cand, $names, true)) return $cand;
        }
        return null;
    }

    protected function findHeader(array $headerRow, array $aliases): ?int
    {
        // return index in $headerRow for the first alias that appears (fuzzy contains)
        $norm = fn($s) => strtolower(preg_replace('/\s+/', '', (string)$s));
        $targets = array_map($norm, $aliases);

        foreach ($headerRow as $idx => $label) {
            $h = $norm($label);
            foreach ($targets as $t) {
                if ($t !== '' && Str::contains($h, $t)) return $idx;
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
            default                                                    => strtoupper(Str::slug($mmcMethod, '_')),
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
