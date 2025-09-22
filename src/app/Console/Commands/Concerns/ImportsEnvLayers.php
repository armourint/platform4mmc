<?php

namespace App\Console\Commands\Concerns;

use App\Models\DatasetVersion;
use App\Models\EnvironmentalFactor;
use App\Models\EnvironmentalLayer;
use App\Support\ExcelHeader;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

trait ImportsEnvLayers
{
    protected function readSheet(string $path, ?string $sheetName = null): array
    {
        $sheets = Excel::toArray(null, $path);
        if ($sheetName === null) {
            return $sheets[0] ?? [];
        }

        // Find by name when available (maatwebsite returns indexed arrays;
        // often sheet order is stable — we’ll just search all for matching header tokens)
        foreach ($sheets as $rows) {
            if (!empty($rows)) {
                // crude presence check: row 0 contains headers; see if it matches known columns
                $header = array_map(fn($h) => ExcelHeader::norm((string)$h), (array)($rows[0] ?? []));
                if (in_array(ExcelHeader::norm('system id'), $header, true)
                    && in_array(ExcelHeader::norm('system name'), $header, true)) {
                    // If the caller gave a sheetName, we rely on them passing the correct sheet index externally.
                    // Many xlsx libs don’t surface the sheet name here; so just return rows (caller ensures correct tab).
                    return $rows;
                }
            }
        }
        return $sheets[0] ?? [];
    }

    protected function headerIndex(array $header): array
    {
        $idx = [];
        foreach ($header as $i => $raw) {
            $key = ExcelHeader::map((string)$raw);
            if ($key) $idx[$key] = $i;
        }
        return $idx;
    }

    protected function val(array $row, array $idx, string $key): mixed
    {
        if (!array_key_exists($key, $idx)) return null;
        $v = $row[$idx[$key]] ?? null;
        if (is_string($v)) $v = trim($v);
        return $v === '' ? null : $v;
    }

    protected function toFloat(mixed $v): ?float
    {
        if ($v === null || $v === '') return null;
        // handle commas as decimal separator if they appear
        if (is_string($v)) $v = str_replace(',', '.', $v);
        return is_numeric($v) ? (float)$v : null;
    }

    protected function normalizeSystemCode(?string $mmcMethod, ?string $systemCode): ?string
    {
        // Prefer an explicit short code column if it ever appears; otherwise derive from MMC Method tokens.
        $code = $systemCode ?: null;
        if (!$code && $mmcMethod) {
            $t = mb_strtoupper($mmcMethod);
            if (str_contains($t, 'LIGHT GAUGE STEEL') || str_contains($t, 'LGS')) $code = 'LGS';
            elseif (str_contains($t, 'TIMBER') || $t === 'TF') $code = 'TF';
            elseif (str_contains($t, 'ICF')) $code = 'ICF';
            elseif (str_contains($t, 'MASONRY') || str_contains($t, 'BLOCK')) $code = 'BLOCK';
        }
        return $code;
    }

    protected function upsertRows(string $datasetVersionLabel, string $module, array $rows): array
    {
        $dataset = DatasetVersion::firstOrCreate(
            ['module' => $module, 'version_label' => $datasetVersionLabel],
            ['status' => 'draft', 'payload' => []]
        );

        $inserted = 0;
        $updated  = 0;

        DB::transaction(function () use ($dataset, $rows, &$inserted, &$updated) {
            // Auto-sequence missing layer_no per assembly
            $seq = [];

            foreach ($rows as $r) {
                // Auto layer_no
                if (!isset($r['layer_no']) || $r['layer_no'] === null) {
                    $key = ($r['system_code'] ?? '') . '|' . ($r['assembly_id'] ?? '');
                    $curr = $seq[$key] ?? 0;
                    $curr++;
                    $seq[$key] = $curr;
                    $r['layer_no'] = $curr;
                }

                // Required keys
                if (!($r['system_code'] ?? null) || !($r['assembly_id'] ?? null)) {
                    continue;
                }

                $payload = array_merge($r, [
                    'dataset_version_id' => $dataset->id,
                ]);

                // Upsert by unique composite
                $found = EnvironmentalLayer::where([
                    'dataset_version_id' => $dataset->id,
                    'system_code'        => $r['system_code'],
                    'assembly_id'        => $r['assembly_id'],
                    'layer_no'           => $r['layer_no'],
                ])->first();

                if ($found) {
                    $found->fill($payload)->save();
                    $updated++;
                } else {
                    EnvironmentalLayer::create($payload);
                    $inserted++;
                }
            }
        });

        return [$inserted, $updated];
    }

    protected function aggregateA1A3(string $datasetVersionLabel): void
    {
        $dataset = DatasetVersion::where('module','environmental')
            ->where('version_label',$datasetVersionLabel)->firstOrFail();

        $bySystem = EnvironmentalLayer::where('dataset_version_id',$dataset->id)
            ->get()
            ->groupBy('system_code');

        DB::transaction(function () use ($dataset, $bySystem) {
            foreach ($bySystem as $systemCode => $layers) {
                $sum = 0.0;
                foreach ($layers as $L) {
                    $v = $L->a1a3_per_m2
                        ?? (isset($L->a1a3_per_5_76_m2) ? ($L->a1a3_per_5_76_m2 / 5.76) : null)
                        ?? (isset($L->mass_kg_m2, $L->carbon_factor) ? ($L->mass_kg_m2 * $L->carbon_factor) : null);
                    if (is_numeric($v)) $sum += (float)$v;
                }
                if ($sum > 0) {
                    EnvironmentalFactor::updateOrCreate(
                        ['dataset_version_id' => $dataset->id, 'system_code' => $systemCode],
                        ['a1_a3_per_m2' => $sum]   // do not touch a4_per_m2 here
                    );
                }
            }
        });
    }

    protected function makeLayerRow(array $row, array $idx): array
    {
        $mmc = $this->val($row, $idx, 'mmc_method');
        $systemCode = $this->normalizeSystemCode($mmc, $this->val($row, $idx, 'system_code'));

        return [
            'system_category'   => $this->val($row, $idx, 'system_category'),
            'assembly_id'       => $this->val($row, $idx, 'assembly_id'),
            'mmc_method'        => $mmc,                          // <— NEW persist
            'system_code'       => $systemCode,                   // normalized short code
            'system_name'       => $this->val($row, $idx, 'system_name'),
            'source_header'     => $this->val($row, $idx, 'source_header'),
            'layer_no'          => $this->toFloat($this->val($row, $idx, 'layer_no')),
            'functional_role'   => $this->val($row, $idx, 'functional_role'),
            'generic_material'  => $this->val($row, $idx, 'generic_material'),
            'length_m'          => $this->toFloat($this->val($row, $idx, 'length_m')),
            'height_m'          => $this->toFloat($this->val($row, $idx, 'height_m')),
            'thickness_m'       => $this->toFloat($this->val($row, $idx, 'thickness_m')),
            'element_volume_m3' => $this->toFloat($this->val($row, $idx, 'element_volume_m3')),
            'element_number'    => $this->toFloat($this->val($row, $idx, 'element_number')),
            'total_volume_m3'   => $this->toFloat($this->val($row, $idx, 'total_volume_m3')),
            'density_kg_m3'     => $this->toFloat($this->val($row, $idx, 'density_kg_m3')),
            'mass_kg_m2'        => $this->toFloat($this->val($row, $idx, 'mass_kg_m2')),
            'carbon_factor'     => $this->toFloat($this->val($row, $idx, 'carbon_factor')),
            'a1a3_per_5_76_m2'  => $this->toFloat($this->val($row, $idx, 'a1a3_per_5_76_m2')),
            'a1a3_per_m2'       => $this->toFloat($this->val($row, $idx, 'a1a3_per_m2')),
        ];
    }
}
