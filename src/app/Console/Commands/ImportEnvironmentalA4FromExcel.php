<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalFactor;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEnvironmentalA4FromExcel extends Command
{
    protected $signature = 'mmc:import-env-a4
        {--path= : Path to ireland_generic_mmc_model.xlsx}
        {--sheet="Wall Systems (A4)" : Sheet name}
        {--dataset-version= : Dataset version label (e.g. v2025.09)}
        {--header-row=1 : Header row number (1-based)}
        {--dump=0 : Preview first N parsed rows (no writes)}
    ';

    protected $description = 'Import A4 (kgCO2e/m²) factors per system from Excel and upsert into environmental_factors';

    public function handle(): int
    {
        $path  = $this->option('path') ?: base_path('ireland_generic_mmc_model.xlsx');
        $sheet = $this->option('sheet') ?: 'Wall Systems (A4)';
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

        $xlsx = IOFactory::load($path);
        $ws   = $xlsx->getSheetByName($sheet);
        if (!$ws) {
            $this->error("Sheet not found: '{$sheet}'. Available: ".implode(', ', $xlsx->getSheetNames()));
            return self::FAILURE;
        }

        $rows = $ws->toArray(null, true, true, true);
        if (count($rows) < $hdrAt) {
            $this->error("Sheet '{$sheet}' has fewer rows than the header row index ({$hdrAt}).");
            return self::FAILURE;
        }

        $headerRow = $rows[$hdrAt] ?? [];
        $map = $this->buildHeaderMap($headerRow);

        $this->line("Mapped headers on '{$sheet}':");
        foreach ($map as $k => $col) $this->line("  - {$k} ⇠ {$col}");

        $parsed = [];
        $startIx = $hdrAt + 1;
        $totalRows = count($rows);

        for ($r = $startIx; $r <= $totalRows; $r++) {
            $cells = $rows[$r] ?? [];
            $mmcMethod  = $this->cell($cells, Arr::get($map, 'mmc_method'));
            $systemName = $this->cell($cells, Arr::get($map, 'system_name'));
            $assemblyId = $this->cell($cells, Arr::get($map, 'assembly_id'));

            // derive system_code from "MMC Method" (same heuristic as the A1–A3 importer)
            $systemCode = $this->deriveSystemCode($mmcMethod, $systemName, $assemblyId);
            if (!$systemCode) {
                // try to salvage from a dedicated system_code column if you later add one
                $systemCode = Str::upper(trim((string)$this->cell($cells, Arr::get($map, 'system_code'))));
            }
            if (!$systemCode) {
                // nothing to key on; skip
                if ($mmcMethod || $systemName) {
                    $this->line("• Skip row {$r}: cannot derive system_code (MMC='{$mmcMethod}', Name='{$systemName}')", 'vv');
                }
                continue;
            }

            // values may be provided either per 5.76 m2 or per m2; compute per m2
            $a4_5p76 = $this->toNum($this->cell($cells, Arr::get($map, 'a4_per_5_76_m2')));
            $a4_m2   = $this->toNum($this->cell($cells, Arr::get($map, 'a4_per_m2')));

            if ($a4_m2 === null && $a4_5p76 !== null) {
                $a4_m2 = $a4_5p76 / 5.76;
            }

            if ($a4_m2 === null) {
                // no value on this row
                continue;
            }

            $parsed[] = [
                'dataset_version_id' => $dataset->id,
                'system_code'        => $systemCode,
                'a4_per_m2'          => $a4_m2,
            ];
        }

        if ($dumpN > 0) {
            $this->info("Dumping first {$dumpN} parsed row(s) (no DB writes):");
            collect($parsed)->take($dumpN)->each(function ($row, $i) {
                $this->line(($i+1).') '.json_encode($row, JSON_UNESCAPED_UNICODE));
            });
            $this->line("Parsed total rows: ".count($parsed));
            return self::SUCCESS;
        }

        $upserts = 0;
        foreach ($parsed as $row) {
            EnvironmentalFactor::updateOrCreate(
                ['dataset_version_id' => $row['dataset_version_id'], 'system_code' => $row['system_code']],
                ['a4_per_m2' => $row['a4_per_m2']]
            );
            $upserts++;
        }

        $this->info("Upserted A4 factors: +{$upserts} (sheet: {$sheet})");
        return self::SUCCESS;
    }

    /* ---------- header mapping & helpers (mirrors A1–A3 importer style) ---------- */

    private function buildHeaderMap(array $headerRow): array
    {
        $norm = [];
        foreach ($headerRow as $col => $val) {
            $key = $this->normHeader($val);
            if ($key) $norm[$key] = $col;
        }

        $want = [
            'assembly_id'      => ['system id', 'assembly id'],
            'system_name'      => ['system name'],
            'mmc_method'       => ['mmc method', 'mmc'],
            'system_code'      => ['system code'],
            // Numbers:
            'a4_per_5_76_m2'   => [
                'a4 kgco2e 5 76 m2',
                'a4 kgco2e per 5 76 m2',
                'a4 kgco2e 5 76',
            ],
            'a4_per_m2'        => [
                'a4 kgco2e m2',
                'a4 kgco2e per m2',
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
        $s = str_replace(['–','—','−'], '-', $s);
        $s = str_replace(['²','/m²','m²'], ['2','/m2','m2'], $s);
        $s = str_replace(['CO₂','co₂','Co₂'], 'CO2', $s);
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
        $s = str_replace([",","\n","\r"], ['.','',''], $s);
        return is_numeric($s) ? (float)$s : null;
    }

    private function deriveSystemCode(?string $mmcMethod, ?string $systemName, ?string $assemblyId): ?string
    {
        $cand = strtoupper(trim((string)$mmcMethod));
        foreach (['LGS','TF','ICF','SIP','CLT'] as $code) {
            if (Str::startsWith($cand, $code)) return $code;
        }
        if (Str::contains($cand, ['MASONRY','BLOCK'])) return 'BLOCK';

        if (preg_match('/^([A-Z]{2,6})\b/', $cand, $m)) {
            return $m[1];
        }

        // Fallbacks
        if ($assemblyId && Str::startsWith($assemblyId, 'WALL_')) return 'BLOCK';
        return null;
    }
}