<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalFactor;
use App\Models\EnvironmentalLayer;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEnvironmentalLayersFromExcel extends Command
{
    protected $signature = 'mmc:import-env-layers
        {--path= : Path to ireland_generic_mmc_model.xlsx}
        {--sheet= : Sheet name. e.g. "Wall Systems", "Cladding Systems", "Slab Systems"}
        {--dataset-version= : Dataset version label (e.g. v2025.09)}
        {--reset : Delete existing rows for this dataset/sheet category before import}
        {--dump=0 : Preview first N parsed rows without writing (0 = disabled)}
        {--header-row=1 : Header row number (1-based)}
    ';

    protected $description = 'Import layered A1–A3 data (per system) from the Excel sheets into environmental_layers (+ aggregate A1–A3 factors).';

    public function handle(): int
    {
        $path  = $this->option('path') ?: base_path('ireland_generic_mmc_model.xlsx');
        $sheet = $this->option('sheet') ?: 'Wall Systems';
        $ver   = $this->option('dataset-version') ?: 'v2025.09';
        $dumpN = (int) $this->option('dump');
        $hdrAt = max(1, (int) $this->option('header-row'));

        if (!is_file($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }

        /** @var DatasetVersion $dataset */
        $dataset = DatasetVersion::firstOrCreate(
            ['module' => 'environmental', 'version_label' => $ver],
            ['status' => 'draft', 'payload' => []]
        );

        // Load sheet by exact name via PhpSpreadsheet
        $xlsx = IOFactory::load($path);
        $ws   = $xlsx->getSheetByName($sheet);
        if (!$ws) {
            $this->error("Sheet not found: '{$sheet}'. Available: ".implode(', ', $xlsx->getSheetNames()));
            return self::FAILURE;
        }

        // Normalize sheet -> default category (fallback if no column present)
        $defaultCategory = $this->inferCategoryFromSheetName($sheet); // Wall | Cladding | Slab

        // Pull rows as a 2D array preserving cell text
        $rows = $ws->toArray(null, true, true, true); // [ [A=>..., B=>..., ...], ... ]

        if (count($rows) < $hdrAt) {
            $this->error("Sheet '{$sheet}' has fewer rows than the header row index ({$hdrAt}).");
            return self::FAILURE;
        }

        // Build header map using the specified header row
        $headerRow = $rows[$hdrAt] ?? [];
        $map = $this->buildHeaderMap($headerRow);

        // Quick visibility for you
        $this->line("Mapped headers on '{$sheet}':");
        foreach ($map as $k => $col) $this->line("  - {$k} ⇠ {$col}");

        // Optionally reset (only for this category in this dataset)
        if ($this->option('reset')) {
            $deleted = EnvironmentalLayer::where('dataset_version_id', $dataset->id)
                ->when($defaultCategory, fn($q)=>$q->where('system_category', $defaultCategory))
                ->delete();
            $this->warn("Reset: deleted {$deleted} existing rows for dataset {$ver} / {$defaultCategory}");
        }

        // Parse body (rows after header)
        $parsed = [];
        $startIx = $hdrAt + 1;
        $totalRows = count($rows);

        for ($r = $startIx; $r <= $totalRows; $r++) {
            $cells = $rows[$r] ?? [];
            // Get essentials
            $assemblyId  = $this->cell($cells, Arr::get($map, 'assembly_id'));
            $mmcMethod   = $this->cell($cells, Arr::get($map, 'mmc_method'));
            $systemName  = $this->cell($cells, Arr::get($map, 'system_name'));
            $srcHeader   = $this->cell($cells, Arr::get($map, 'source_header'));
            $layerNo     = $this->toInt($this->cell($cells, Arr::get($map, 'layer_no')));

            // Skip blank/summary rows (no assembly, no mmc, no name & no numbers)
            if (!$assemblyId && !$mmcMethod && !$systemName) {
                continue;
            }

            // Derive system_code from MMC Method; keep mmc_method verbatim too
            $systemCode = $this->deriveSystemCode($mmcMethod, $systemName, $assemblyId);
            if (!$systemCode) {
                // If we can’t figure out a system code, we can’t key the record – skip
                $this->line("• Skip row {$r}: cannot derive system_code (MMC Method='{$mmcMethod}')", 'vv');
                continue;
            }

            $systemCategory = $this->cell($cells, Arr::get($map, 'system_category')) ?: $defaultCategory;

            // Numerics (many may be blank on some sheets)
            $lengthM   = $this->toNum($this->cell($cells, Arr::get($map, 'length_m')));
            $heightM   = $this->toNum($this->cell($cells, Arr::get($map, 'height_m')));
            $thickM    = $this->toNum($this->cell($cells, Arr::get($map, 'thickness_m')));
            $elemVol   = $this->toNum($this->cell($cells, Arr::get($map, 'element_volume_m3')));
            $elemNo    = $this->toInt($this->cell($cells, Arr::get($map, 'element_number')));
            $totalVol  = $this->toNum($this->cell($cells, Arr::get($map, 'total_volume_m3')));
            $density   = $this->toNum($this->cell($cells, Arr::get($map, 'density_kg_m3')));
            $massKgM2  = $this->toNum($this->cell($cells, Arr::get($map, 'mass_kg_m2')));
            $carbonFac = $this->toNum($this->cell($cells, Arr::get($map, 'carbon_factor')));

            $a1a3_5p76 = $this->toNum($this->cell($cells, Arr::get($map, 'a1a3_per_5_76_m2')));
            $a1a3_m2   = $this->toNum($this->cell($cells, Arr::get($map, 'a1a3_per_m2')));

            if ($a1a3_m2 === null && $a1a3_5p76 !== null) {
                $a1a3_m2 = $a1a3_5p76 / 5.76;
            }

            $parsed[] = [
                'dataset_version_id' => $dataset->id,
                'system_code'        => $systemCode,
                'assembly_id'        => $assemblyId ?: null,
                'mmc_method'         => $mmcMethod ?: null,
                'system_name'        => $systemName ?: null,
                'system_category'    => $systemCategory ?: null,
                'source_header'      => $srcHeader ?: null,
                'layer_no'           => $layerNo,

                'functional_role'    => $this->cell($cells, Arr::get($map, 'functional_role')) ?: null,
                'generic_material'   => $this->cell($cells, Arr::get($map, 'generic_material')) ?: null,

                'length_m'           => $lengthM,
                'height_m'           => $heightM,
                'thickness_m'        => $thickM,
                'element_volume_m3'  => $elemVol,
                'element_number'     => $elemNo,
                'total_volume_m3'    => $totalVol,
                'density_kg_m3'      => $density,
                'mass_kg_m2'         => $massKgM2,
                'carbon_factor'      => $carbonFac,
                'a1a3_per_5_76_m2'   => $a1a3_5p76,
                'a1a3_per_m2'        => $a1a3_m2,
            ];
        }

        // Preview only?
        if ($dumpN > 0) {
            $this->info("Dumping first {$dumpN} parsed row(s) (no DB writes):");
            collect($parsed)->take($dumpN)->each(function ($row, $i) {
                $this->line(($i+1).') '.json_encode($row, JSON_UNESCAPED_UNICODE));
            });
            $this->line("Parsed total rows: ".count($parsed));
            return self::SUCCESS;
        }

        // Upsert rows
        $upserts = 0;
        DB::transaction(function () use ($parsed, &$upserts) {
            foreach ($parsed as $row) {
                // Unique key per spec:
                $match = Arr::only($row, ['dataset_version_id','system_code','assembly_id','layer_no']);
                EnvironmentalLayer::updateOrCreate($match, Arr::except($row, array_keys($match)));
                $upserts++;
            }
        });

        $this->info("Upserted layers: +{$upserts} / ~ ".count($parsed)." for sheet [{$sheet}]");

        // Aggregate A1–A3 per system_code (sum the layer a1a3_per_m2 if present)
        $agg = EnvironmentalLayer::query()
            ->selectRaw('system_code, SUM(COALESCE(a1a3_per_m2,0)) as s')
            ->where('dataset_version_id', $dataset->id)
            ->when($defaultCategory, fn($q)=>$q->where('system_category', $defaultCategory))
            ->groupBy('system_code')
            ->pluck('s', 'system_code')
            ->toArray();

        foreach ($agg as $code => $sum) {
            EnvironmentalFactor::updateOrCreate(
                ['dataset_version_id'=>$dataset->id, 'system_code'=>$code],
                ['a1_a3_per_m2' => ($sum > 0 ? $sum : null)] // leave A4 untouched here
            );
        }

        $codes = implode(', ', array_keys($agg));
        $this->info("Aggregated A1–A3 factors per system_code: {$codes}");

        return self::SUCCESS;
    }

    /* ---------- helpers ---------- */

    private function inferCategoryFromSheetName(string $sheet): ?string
    {
        $s = Str::lower($sheet);
        return match (true) {
            Str::contains($s, 'wall')     => 'Wall',
            Str::contains($s, 'cladding') => 'Cladding',
            Str::contains($s, 'slab')     => 'Slab',
            default => null,
        };
    }

    private function buildHeaderMap(array $headerRow): array
    {
        // Normalize cell text -> canonical field name
        $norm = [];
        foreach ($headerRow as $col => $val) {
            $key = $this->normHeader($val);
            if ($key) $norm[$key] = $col; // e.g. 'system id' => 'B'
        }

        // Known mappings (synonyms tolerated)
        $want = [
            'system_category'      => ['system category'],
            'assembly_id'          => ['system id', 'assembly id'],
            'mmc_method'           => ['mmc method', 'mmc'],
            'system_name'          => ['system name'],
            'source_header'        => ['source header'],
            'layer_no'             => ['layer no', 'layer no.'],
            'functional_role'      => ['functional role'],
            'generic_material'     => ['generic material'],

            'length_m'             => ['length m'],
            'height_m'             => ['height m'],
            'thickness_m'          => ['thickness m'],

            'element_volume_m3'    => ['element volume m3', 'element vol m3'],
            'element_number'       => ['element number', 'elem number', 'element no'],
            'total_volume_m3'      => ['total volume m3', 'total vol m3'],

            'density_kg_m3'        => ['density kg m3', 'density kg.m3'],
            'mass_kg_m2'           => ['mass kg m2', 'mass kg m²', 'mass kg per m2'],
            'carbon_factor'        => ['carbon factor'],

            'a1a3_per_5_76_m2'     => [
                'a1 a3 kgco2e 5 76 m2',
                'a1 a3 kgco2e per 5 76 m2',
                'a1–a3 kgco2e 5 76 m2',
                'a1-a3 kgco2e 5 76 m2',
            ],
            'a1a3_per_m2'          => [
                'a1 a3 kgco2e m2',
                'a1–a3 kgco2e m2',
                'a1-a3 kgco2e m2',
            ],
        ];

        $map = [];
        foreach ($want as $field => $candidates) {
            foreach ($candidates as $cand) {
                if (isset($norm[$cand])) {
                    $map[$field] = $norm[$cand];
                    break;
                }
            }
        }

        return $map;
    }

    private function normHeader($v): ?string
    {
        $s = trim((string)$v);
        if ($s === '') return null;

        // Replace unicode dashes and CO₂ with ascii
        $s = str_replace(['–','—','−'], '-', $s);
        $s = str_replace(['²','/m²','m²'], ['2','/m2','m2'], $s);
        $s = str_replace(['CO₂','co₂','Co₂'], 'CO2', $s);

        // strip everything that’s not a-z0-9 or space
        $s = Str::lower($s);
        $s = preg_replace('/[^a-z0-9]+/',' ', $s);
        $s = trim(preg_replace('/\s+/',' ', $s));
        return $s;
    }

    private function cell(array $row, ?string $col)
    {
        return $col ? (isset($row[$col]) ? trim((string)$row[$col]) : null) : null;
    }

    private function toNum($v): ?float
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        // tolerate commas, newlines, etc.
        $s = str_replace([",","\n","\r"], ['.','',''], $s);
        return is_numeric($s) ? (float)$s : null;
    }

    private function toInt($v): ?int
    {
        $f = $this->toNum($v);
        return $f === null ? null : (int) round($f);
    }

    private function deriveSystemCode(?string $mmcMethod, ?string $systemName, ?string $assemblyId): ?string
    {
        $cand = strtoupper(trim((string)$mmcMethod));

        // Common cases
        foreach (['LGS','TF','ICF','SIP','CLT'] as $code) {
            if (Str::startsWith($cand, $code)) return $code;
        }
        if (Str::contains($cand, ['MASONRY','BLOCK'])) return 'BLOCK';

        // Try the first token (if it looks like an all-caps abbreviation)
        if (preg_match('/^([A-Z]{2,6})\b/', $cand, $m)) {
            return $m[1];
        }

        // Fallbacks
        if ($assemblyId && Str::startsWith($assemblyId, 'WALL_')) return 'BLOCK'; // sensible default for masonry sheets
        return null;
    }
}