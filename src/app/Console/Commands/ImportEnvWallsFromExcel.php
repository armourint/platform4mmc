<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalLayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportEnvWallsFromExcel extends Command
{
    protected $signature = 'mmc:import-env-walls
        {--path= : Path to ireland_generic_mmc_model.xlsx}
        {--sheet="Wall Systems" : Sheet name}
        {--dataset-version=v2025.09 : Dataset version label}
        {--dry-run : Parse and report counts, don\'t write}
        {--replace : Delete existing layers for this dataset+sheet before import}
    ';

    protected $description = 'Import layer-by-layer A1–A3 wall data into environmental_layers';

    public function handle(): int
    {
        $path  = $this->option('path') ?: base_path('ireland_generic_mmc_model.xlsx');
        $sheet = $this->option('sheet') ?: 'Wall Systems';
        $ver   = $this->option('dataset-version') ?: 'v2025.09';
        $dry   = (bool) $this->option('dry-run');
        $wipe  = (bool) $this->option('replace');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $dataset = DatasetVersion::firstOrCreate(
            ['module' => 'environmental', 'version_label' => $ver],
            ['status' => 'draft']
        );

        // Load all sheets and pick the one we need
        $all = Excel::toArray(null, $path);
        $target = null;
        foreach ($all as $s) {
            // First row is header; allow picking by sheet name is not guaranteed by maatwebsite/excel in toArray
            // So we heuristically pick the sheet that has the expected "System Category" header
            if (is_array($s) && isset($s[0]) && is_array($s[0])) {
                $hdr = $this->normalizeHeaderRow($s[0]);
                if (in_array('systemcategory', $hdr, true) && in_array('systemid', $hdr, true)) {
                    // We can’t see the real sheet names here; assume caller passed correct one
                    $target = $s;
                    break;
                }
            }
        }
        if (!$target) {
            $this->error("Couldn’t read sheet rows for \"{$sheet}\".");
            return self::FAILURE;
        }

        $rows = $target;
        if (count($rows) < 2) {
            $this->warn('No data rows found.');
            return self::SUCCESS;
        }

        // Build header map
        $rawHeader = array_shift($rows);
        $normHeader = $this->normalizeHeaderRow($rawHeader);
        $i = fn(string $key) => array_search($key, $normHeader, true);

        // Expected keys after normalization
        $col = [
            'system_category' => $i('systemcategory'),
            'system_id'       => $i('systemid'),
            'mmc_method'      => $i('mmcmethod'),
            'system_name'     => $i('systemname'),
            'source_header'   => $i('sourceheader'),
            'layer_no'        => $i('layerno'),
            'functional_role' => $i('functionalrole'),
            'generic_material'=> $i('genericmaterial'),
            'length_m'        => $i('lengthm'),
            'height_m'        => $i('heightm'),
            'thickness_m'     => $i('thicknessm'),
            'element_volume'  => $i('elementvolumem3'),
            'element_number'  => $i('elementnumber'),
            'total_volume'    => $i('totalvolumem3'),
            'density'         => $i('densitykgm3'),
            'mass_per_m2'     => $i('masskgm2'),
            'carbon_factor'   => $i('carbonfactor'),
            'a1a3_5_76'       => $i('a1-3kgco2e576m2'),     // “A1–A3 (kgCO₂e / 5.76 m²)”
            'a1a3_per_m2'     => $i('a1-3kgco2em2'),        // “A1–A3 (kgCO₂e/m²)”
        ];

        // Quick sanity: we at least need system_id / system_name / layer_no columns
        foreach (['system_id','system_name','layer_no'] as $must) {
            if ($col[$must] === false) {
                $this->warn("Missing expected column: {$must} (normalized). Import will continue but values may be NULL.");
            }
        }

        $toInsert = [];
        $skipped  = 0;

        foreach ($rows as $r) {
            // Skip fully empty rows
            if ($this->allEmpty($r)) continue;

            $systemId   = $this->val($r, $col['system_id']);
            $systemName = $this->val($r, $col['system_name']);

            // If both key identifiers are blank, skip row
            if ($systemId === '' && $systemName === '') { $skipped++; continue; }

            $systemCode = $this->inferSystemCode($this->val($r, $col['mmc_method'])); // LGS/TF/ICF/BLOCK etc.

            $row = [
                'dataset_version_id' => $dataset->id,
                'system_code'        => $systemCode ?: null,
                'assembly_id'        => $systemId ?: null,
                'system_name'        => $systemName ?: null,
                'system_category'    => $this->val($r, $col['system_category']) ?: null,
                'source_header'      => $this->val($r, $col['source_header']) ?: 'A1-A3 Walls',
                'layer_no'           => $this->toInt($this->val($r, $col['layer_no'])),
                'functional_role'    => $this->val($r, $col['functional_role']) ?: null,
                'generic_material'   => $this->val($r, $col['generic_material']) ?: null,
                'length_m'           => $this->toFloat($this->val($r, $col['length_m'])),
                'height_m'           => $this->toFloat($this->val($r, $col['height_m'])),
                'thickness_m'        => $this->toFloat($this->val($r, $col['thickness_m'])),
                'element_volume_m3'  => $this->toFloat($this->val($r, $col['element_volume'])),
                'element_number'     => $this->toInt($this->val($r, $col['element_number'])),
                'total_volume_m3'    => $this->toFloat($this->val($r, $col['total_volume'])),
                'density_kg_m3'      => $this->toFloat($this->val($r, $col['density'])),
                'mass_kg_m2'         => $this->toFloat($this->val($r, $col['mass_per_m2'])),
                'carbon_factor'      => $this->toFloat($this->val($r, $col['carbon_factor'])),
                'a1a3_per_5_76_m2'   => $this->toFloat($this->val($r, $col['a1a3_5_76'])),
                'a1a3_per_m2'        => $this->toFloat($this->val($r, $col['a1a3_per_m2'])),
            ];

            $toInsert[] = $row;
        }

        $this->info("Parsed rows: ".count($rows).", to insert/update: ".count($toInsert).", skipped (empty-id+name): {$skipped}");

        if ($dry) {
            $this->line('Dry-run complete (no DB writes).');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($dataset, $wipe, $toInsert) {
            if ($wipe) {
                EnvironmentalLayer::where('dataset_version_id', $dataset->id)
                    ->where('source_header', 'A1-A3 Walls')
                    ->delete();
            }

            foreach ($toInsert as $row) {
                EnvironmentalLayer::updateOrCreate(
                    [
                        'dataset_version_id' => $row['dataset_version_id'],
                        'system_code'        => $row['system_code'],
                        'assembly_id'        => $row['assembly_id'],
                        'layer_no'           => $row['layer_no'],
                    ],
                    $row
                );
            }
        });

        $this->info('Import finished.');
        return self::SUCCESS;
    }

    /** Normalize a header row into simple keys (lower, remove spaces/punct, unify unicode dashes) */
    private function normalizeHeaderRow(array $row): array
    {
        return array_map(function ($h) {
            $h = mb_strtolower((string)$h);
            $h = str_replace(['–','—','−'], '-', $h);  // unicode dashes
            $h = preg_replace('/[^a-z0-9]+/u', '', $h); // keep a-z0-9
            return $h;
        }, $row);
    }

    private function allEmpty(array $row): bool
    {
        foreach ($row as $v) { if (trim((string)$v) !== '') return false; }
        return true;
    }

    private function val(array $row, $idx) { return ($idx !== false && isset($row[$idx])) ? trim((string)$row[$idx]) : ''; }
    private function toInt($v) { $v = trim((string)$v); return $v === '' ? null : (int)round((float)str_replace([','], [''], $v)); }
    private function toFloat($v)
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        $v = str_replace([','], [''], $v);
        return is_numeric($v) ? (float)$v : null;
    }

    private function inferSystemCode(?string $mmcMethod): ?string
    {
        if (!$mmcMethod) return null;
        $m = mb_strtolower($mmcMethod);
        return match(true) {
            str_contains($m, 'light gauge steel') => 'LGS',
            str_contains($m, 'timber')            => 'TF',
            str_contains($m, 'icf')               => 'ICF',
            str_contains($m, 'block')             => 'BLOCK',
            default                               => null,
        };
    }
}
