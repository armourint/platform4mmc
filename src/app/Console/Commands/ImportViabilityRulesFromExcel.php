<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\DataImport;
use App\Models\Rule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportViabilityRulesFromExcel extends Command
{
    protected $signature = 'mmc:import-viability-rules
        {--path= : Path to Excel (absolute or storage/app/public relative)}
        {--import-id= : data_imports.id to resolve file + dataset_version_id}
        {--dataset-version= : Version label (only used if a new draft must be created)}
        {--dataset-version-id= : Reuse this dataset_versions.id (never create another)}
        {--reset : Delete existing rules for this dataset version first}
        {--sheet=viability_rules : Sheet name (default: viability_rules)}';

    protected $description = 'Import simplified viability rules from mmc_viability_data.xlsx into rules for a draft dataset version.';

    /** Human → system_code mapping (adjust to your canonical codes) */
    private array $methodToSystem = [
        'Concrete Block' => 'BLOCK',
        'ICF'            => 'ICF',
        'LGS'            => 'LGS',
        'Timber'         => 'TIMBER',
    ];

    public function handle(): int
    {
        $sheetName = (string) ($this->option('sheet') ?: 'viability_rules');

        /** -------------------------------------------------------------
         * 1) Decide which dataset_version to use (REUSE if provided)
         * ------------------------------------------------------------ */
        $dv = null;

        // Highest precedence: explicit dataset version id
        if ($id = $this->option('dataset-version-id')) {
            $dv = DatasetVersion::find($id);
            if (!$dv) {
                $this->error("DatasetVersion #{$id} not found.");
                return self::FAILURE;
            }
            $this->info("Using existing DatasetVersion #{$dv->id} ({$dv->module}/{$dv->status}/{$dv->version_label}).");
        }

        // Next: via import-id
        $import = null;
        if ($importId = $this->option('import-id')) {
            $import = DataImport::find($importId);
            if (!$import) {
                $this->error("DataImport #{$importId} not found.");
                return self::FAILURE;
            }
            if (!$dv && $import->dataset_version_id) {
                $dv = DatasetVersion::find($import->dataset_version_id);
                if ($dv) {
                    $this->info("Reusing DatasetVersion from DataImport: #{$dv->id} ({$dv->module}/{$dv->status}/{$dv->version_label}).");
                }
            }
        }

        // Finally: create/reuse a draft by label only if we still don't have one
        if (!$dv) {
            $label = (string) ($this->option('dataset-version') ?: 'viability-'.date('Ymd-His'));
            $dv = DatasetVersion::firstOrCreate(
                ['module' => 'viability', 'status' => 'draft', 'version_label' => $label],
                []
            );
            $this->info("Created/reused draft DatasetVersion #{$dv->id} (label={$label}).");

            // Backfill link on DataImport if present
            if ($import && !$import->dataset_version_id) {
                $import->dataset_version_id = $dv->id;
                $import->save();
            }
        }

        $labelForLog = $dv->version_label ?? '(no-label)';

        /** -------------------------------------------------------------
         * 2) Resolve the Excel file path
         * ------------------------------------------------------------ */
        $pathOpt = (string) ($this->option('path') ?? '');
        if ($import && !$pathOpt) {
            $disk = $import->disk ?: 'public';
            $path = Storage::disk($disk)->path($import->path);
            $this->info("Resolved file from DataImport disk/path: {$path}");
        } else {
            $path = $this->resolvePath($pathOpt);
            $this->info("Resolved file from --path: {$path}");
        }

        if (!is_readable($path)) {
            $this->error("Excel file not readable at: {$path}");
            return self::FAILURE;
        }

        /** -------------------------------------------------------------
         * 3) Optional reset of rules for this dataset version
         * ------------------------------------------------------------ */
        if ($this->option('reset')) {
            Rule::where('dataset_version_id', $dv->id)->delete();
            $this->info("Cleared existing rules for dataset_version #{$dv->id} ({$labelForLog}).");
        }

        /** -------------------------------------------------------------
         * 4) Load workbook & sheet
         * ------------------------------------------------------------ */
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (!$sheet) {
            $this->error("Sheet '{$sheetName}' not found.");
            return self::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 2) {
            $this->warn('No data rows found (need header + at least one data row).');
            return self::SUCCESS;
        }

        /** -------------------------------------------------------------
         * 5) Normalize headers & map required columns
         * ------------------------------------------------------------ */
        $rawHeaders  = array_values($rows[1] ?? []);
        $normHeaders = array_map([$this, 'norm'], $rawHeaders);
        $colIndex    = fn(string $want) => array_search($want, $normHeaders, true);

        // Expected logical columns → normalized header keys we search for
        $need = [
            'mmc_method'                => 'mmc_method',
            'low_rise'                  => 'low_rise',
            'medium_rise'               => 'medium_rise',
            'high_rise'                 => 'high_rise',
            'on_site_storage'           => 'on_site_storage',
            'off_site_storage'          => 'off_site_storage',
            'tower_crane'               => 'tower_crane',
            'telescopic_crane'          => 'telescopic_crane',
            'telehandler_crane'         => 'telehandler_crane',
            'flatbed_truck'             => 'flatbed_truck',
            'flatbed_a_frame'           => 'flatbed_a_frame',
            'max_panel_height_m'        => 'max_panel_height_m',
            'max_frame_length_m'        => 'max_frame_length_m',
            'max_frame_width_le_3_2_m'  => 'max_frame_width_less_than_3_2_m',
            'max_frame_width_gt_3_2_m'  => 'max_frame_width_more_than_3_2_m',
        ];

        $idx = [];
        foreach ($need as $logical => $key) {
            $i = $colIndex($key);
            $idx[$logical] = $i !== false ? $i : null;
        }

        if ($idx['mmc_method'] === null) {
            $this->error("Required column 'MMC Method' not found in header.");
            $this->line('Headers (normalized): '.implode(' | ', $normHeaders));
            return self::FAILURE;
        }

        /** -------------------------------------------------------------
         * 6) Row parsing helpers
         * ------------------------------------------------------------ */
        $truthy = function ($v): bool {
            if (is_bool($v)) return $v;
            $s = strtoupper(trim((string)$v));
            return in_array($s, ['1','Y','YES','TRUE','T','✔','✓','ALLOW','SUPPORTED'], true);
        };
        $toFloat = function ($v): ?float {
            if ($v === null || $v === '') return null;
            $s = str_replace(',', '.', (string) $v);
            return is_numeric($s) ? (float) $s : null;
        };

        /** -------------------------------------------------------------
         * 7) Build exclude rules for unsupported facets
         * ------------------------------------------------------------ */
        $created = 0;
        $priorityBase = 100;

        DB::beginTransaction();
        try {
            $total = count($rows);

            for ($r = 2; $r <= $total; $r++) {
                $row = array_values($rows[$r] ?? []);
                if (!isset($row[0])) continue;

                $method = trim((string) ($row[$idx['mmc_method']] ?? ''));
                if ($method === '') continue;

                // Resolve system_code (and system_id if you have a systems table)
                $systemCode = $this->methodToSystem[$method] ?? Str::upper(Str::slug($method));
                $systemId = null;
                if (class_exists(\App\Models\System::class)) {
                    $sys = \App\Models\System::where('code', $systemCode)->first();
                    $systemId = $sys?->id;
                }

                $p = $priorityBase;

                // 1) Residential type → exclude when unsupported
                $resKeys = [
                    'low'    => $idx['low_rise'],
                    'medium' => $idx['medium_rise'],
                    'high'   => $idx['high_rise'],
                ];
                foreach ($resKeys as $enum => $i) {
                    if ($i === null) continue;
                    if (!$truthy($row[$i] ?? null)) {
                        $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                            'residential_type' => ['eq' => $enum],
                        ], "Not suitable for ".ucfirst($enum)." rise");
                        $created++;
                    }
                }

                // 2) Storage (multi)
                $storageMap = [
                    'on-site'  => $idx['on_site_storage'],
                    'off-site' => $idx['off_site_storage'],
                ];
                foreach ($storageMap as $enum => $i) {
                    if ($i === null) continue;
                    if (!$truthy($row[$i] ?? null)) {
                        $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                            'storage_types' => ['contains' => $enum],
                        ], ucfirst(str_replace('-', ' ', $enum)).' storage not supported');
                        $created++;
                    }
                }

                // 3) Crane (multi)
                $craneMap = [
                    'tower_crane'      => $idx['tower_crane'],
                    'telescopic_crane' => $idx['telescopic_crane'],
                    'telehandler'      => $idx['telehandler_crane'],
                ];
                foreach ($craneMap as $enum => $i) {
                    if ($i === null) continue;
                    if (!$truthy($row[$i] ?? null)) {
                        $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                            'machinery' => ['contains' => $enum],
                        ], Str::of($enum)->replace('_', ' ')->title().' not supported');
                        $created++;
                    }
                }

                // 4) Truck (multi)
                $truckMap = [
                    'flatbed_truck'    => $idx['flatbed_truck'],
                    'flatbed_a_frame'  => $idx['flatbed_a_frame'],
                ];
                foreach ($truckMap as $enum => $i) {
                    if ($i === null) continue;
                    if (!$truthy($row[$i] ?? null)) {
                        $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                            'truck_types' => ['contains' => $enum],
                        ], Str::of($enum)->replace('_', ' ')->title().' not supported');
                        $created++;
                    }
                }

                // 5) Panel height band — if max <= 3.0 → exclude when user picks > 3.0m
                $panelMax = $toFloat($row[$idx['max_panel_height_m']] ?? null);
                if ($panelMax !== null && $panelMax <= 3.0) {
                    $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                        'panel_height_band' => ['eq' => '>3.0m'],
                    ], 'Panel height > 3.0 m not supported');
                    $created++;
                }

                // 6) Frame length band — if max < 12.0 → exclude when user picks > 12.0m
                $lenMax = $toFloat($row[$idx['max_frame_length_m']] ?? null);
                if ($lenMax !== null && $lenMax < 12.0) {
                    $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                        'max_frame_length_band' => ['eq' => '>12.0m'],
                    ], 'Frame length > 12.0 m not supported');
                    $created++;
                }

                // 7) Frame width bands — explicit booleans
                $le = $this->toBool($row[$idx['max_frame_width_le_3_2_m']] ?? null);
                $gt = $this->toBool($row[$idx['max_frame_width_gt_3_2_m']] ?? null);

                if ($gt === false) {
                    $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                        'max_frame_width_band' => ['eq' => '>3.2m'],
                    ], 'Frame width > 3.2 m not supported');
                    $created++;
                }
                if ($le === false) {
                    $this->insertRule($dv->id, $systemId, $systemCode, 'exclude', $p += 10, [
                        'max_frame_width_band' => ['eq' => '<=3.2m'],
                    ], 'Frame width ≤ 3.2 m not supported');
                    $created++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Imported {$created} exclude rules into dataset_version #{$dv->id} ({$labelForLog}).");
        $this->line('Publish with your "Make current" UI when ready.');
        return self::SUCCESS;
    }

    private function insertRule(
        int $datasetVersionId,
        ?int $systemId,
        string $systemCode,
        string $ruleType,
        int $priority,
        array $conditions,
        string $reason
    ): void {
        Rule::create([
            'dataset_version_id' => $datasetVersionId,
            'module'             => 'viability',
            'system_id'          => $systemId,          // nullable if your schema allows
            'system_code'        => $systemCode,
            'rule_type'          => $ruleType,          // 'exclude' or 'include'
            'priority'           => $priority,
            'conditions_json'    => $conditions,        // your column name
            'reason'             => $reason,
        ]);
    }

    /** Normalize header strings (lowercase, remove spaces/punct, replace locale commas) */
    private function norm(?string $s): string
    {
        $s = (string) $s;
        $s = strtr($s, [',' => '_', '’' => "'", '–'=>'-','—'=>'-','/'=>'_','\\'=>'_','('=>'',')'=>'','.'=>'','+'=>' plus ']);
        $s = preg_replace('/\s+/', '_', trim($s));
        $s = preg_replace('/[^a-zA-Z0-9_]/', '', $s);
        return strtolower($s);
    }

    private function toBool($v): ?bool
    {
        if ($v === null || $v === '') return null;
        $s = strtoupper(trim((string)$v));
        if (in_array($s, ['1','Y','YES','TRUE','T','✔','✓','ALLOW','SUPPORTED'], true)) return true;
        if (in_array($s, ['0','N','NO','FALSE','F','✘','X','✗','UNSUPPORTED'], true)) return false;
        return null;
    }

    private function resolvePath(string $pathOpt): string
    {
        // absolute inside container
        if ($pathOpt && str_starts_with($pathOpt, '/')) {
            return $pathOpt;
        }
        // treat as storage/app/public relative (how uploads are stored)
        return storage_path('app/public/'.ltrim($pathOpt, '/'));
    }
}
