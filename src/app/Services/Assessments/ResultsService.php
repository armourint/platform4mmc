<?php

namespace App\Services\Assessments;

use App\Models\Rule;
use App\Models\EnvironmentalLayer;
use Illuminate\Support\Collection;

class ResultsService
{
    /** Resolve a system_code for this assessment, or best-effort fallback. */
    public static function resolveSystemCode($assessment): ?string
    {
        // Try common places first (adjust if your schema differs)
        $code = data_get($assessment, 'system_code')
            ?? data_get($assessment, 'data.system_code')
            ?? data_get($assessment, 'meta.system_code');

        if ($code) return $code;

        // Fallback: most frequent system_code in layers (quick demo safe)
        return EnvironmentalLayer::query()
            ->selectRaw('system_code, COUNT(*) as c')
            ->whereNotNull('system_code')
            ->groupBy('system_code')
            ->orderByDesc('c')
            ->value('system_code');
    }

    /** Viability summary for a given system_code. */
    public static function viabilitySummary(?string $systemCode): array
    {
        if (!$systemCode) {
            return [
                'status' => 'unknown',
                'include_count' => 0,
                'exclude_count' => 0,
                'failed' => [],
                'notes' => 'No system selected.',
            ];
        }

        $rules = Rule::query()->where('system_code', $systemCode)->get();

        $include = $rules->where('rule_type', 'include');
        $exclude = $rules->where('rule_type', 'exclude');

        // Demo-friendly logic:
        // - If there are any "exclude" rules, mark as "attention"
        // - If zero excludes -> "pass"
        $status = $exclude->isNotEmpty() ? 'attention' : 'pass';

        // Try to extract human-readable reasons from common columns if present
        $failed = $exclude->map(function ($r) {
            $attrs = $r->getAttributes();
            // pick likely descriptive bits
            $bits = [];
            foreach (['reason','note','notes','constraint','crane_type','max_length','max_height','transport'] as $k) {
                if (!empty($attrs[$k])) $bits[] = "{$k}: {$attrs[$k]}";
            }
            $msg = $bits ? implode(', ', $bits) : 'Excluded by rule';
            return [
                'id' => $r->id,
                'message' => $msg,
            ];
        })->values()->all();

        return [
            'status'        => $status,               // pass | attention | unknown
            'include_count' => $include->count(),
            'exclude_count' => $exclude->count(),
            'failed'        => $failed,               // list of messages
            'notes'         => $rules->isEmpty() ? 'No rules found for this system.' : null,
        ];
    }

    /** Environmental snapshot (A1–A3 + hotspots) for a system_code, per m². */
    public static function environmentalSnapshot(?string $systemCode): array
    {
        if (!$systemCode) {
            return [
                'a1a3_total' => null,
                'a4_total'   => null,
                'hotspots'   => [],
                'layers'     => [],
                'notes'      => 'No system selected.',
            ];
        }

        $rows = EnvironmentalLayer::query()
            ->where('system_code', $systemCode)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'a1a3_total' => null,
                'a4_total'   => null,
                'hotspots'   => [],
                'layers'     => [],
                'notes'      => 'No environmental layers for this system.',
            ];
        }

        // Helper to get first non-null attr from possible keys
        $get = function ($row, array $candidates, $default = null) {
            $attrs = $row->getAttributes();
            foreach ($candidates as $k) {
                if (array_key_exists($k, $attrs) && $attrs[$k] !== null && $attrs[$k] !== '') {
                    return is_string($attrs[$k]) && is_numeric($attrs[$k]) ? (float)$attrs[$k] : $attrs[$k];
                }
            }
            return $default;
        };

        $layerSummaries = $rows->map(function ($r) use ($get) {
            $th_mm  = $get($r, ['thickness_mm','thickness'], 0.0);
            $dens   = $get($r, ['density_kg_m3','density'], 0.0);
            $a1a3_kg_per_kg = $get($r, ['gwp_a1a3_per_kg','gwp_a1_a3_per_kg','a1a3_per_kg','gwp_kgco2e_per_kg']);
            $a1a3_per_m2    = $get($r, ['gwp_a1a3_per_m2','gwp_a1_a3_per_m2','a1a3_kgco2e_per_m2']);

            // mass per m2 (kg/m2) if thickness + density are known
            $mass_m2 = ($th_mm > 0 && $dens > 0) ? ($th_mm / 1000.0) * $dens : null;

            // Prefer per-m2 factor if present; otherwise mass * per-kg factor
            $a1a3 = null;
            if ($a1a3_per_m2 !== null) {
                $a1a3 = (float) $a1a3_per_m2;
            } elseif ($mass_m2 !== null && $a1a3_kg_per_kg !== null) {
                $a1a3 = $mass_m2 * (float) $a1a3_kg_per_kg;
            }

            // Optional A4 transport (if present)
            $a4 = $get($r, ['gwp_a4_per_m2','a4_kgco2e_per_m2']);

            return [
                'layer_no'  => $get($r, ['layer_no','layer','seq'], null),
                'material'  => $get($r, ['material','name','description'], 'Layer'),
                'thickness_mm' => $th_mm ?: null,
                'density_kg_m3'=> $dens ?: null,
                'mass_kg_m2'   => $mass_m2,
                'a1a3_kgco2e_m2' => $a1a3,
                'a4_kgco2e_m2'   => $a4,
            ];
        });

        // Totals (ignore nulls)
        $a1a3_total = self::sumNullable($layerSummaries->pluck('a1a3_kgco2e_m2'));
        $a4_total   = self::sumNullable($layerSummaries->pluck('a4_kgco2e_m2'));

        // Hotspots = top 5 by A1–A3 contribution (fallback to mass)
        $hotspots = $layerSummaries
            ->map(fn($x) => [
                'label' => $x['material'],
                'value' => $x['a1a3_kgco2e_m2'] ?? ($x['mass_kg_m2'] ?? 0),
            ])
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();

        return [
            'a1a3_total' => $a1a3_total,
            'a4_total'   => $a4_total,
            'hotspots'   => $hotspots,
            'layers'     => $layerSummaries->values()->all(),
            'notes'      => null,
        ];
    }

    private static function sumNullable(Collection $nums): ?float
    {
        $vals = $nums->filter(fn($v)=>is_numeric($v))->values();
        return $vals->isEmpty() ? null : round($vals->sum(), 3);
    }
}
