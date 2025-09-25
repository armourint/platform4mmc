<?php

namespace App\Services\DST;

use App\Models\DatasetVersion;
use App\Models\Rule;
use App\Models\System;

final class ViabilityEvaluator
{
    /**
     * Evaluate viability against the given dataset version.
     *
     * @param  array $inputs  // expected keys: residential_type, storage_type, machinery[], stories, height_m, res_units, commercial_units, storage_space_m2,
     *                        // tower_crane_capacity_t, telescopic_crane_capacity_t, telehandler_capacity_t
     * @param  \App\Models\DatasetVersion $dv
     * @return array{
     *   per_system: array<string, array{ok: bool, failed: array<int, array{reason: string, rule_id:int|null}>}>,
     *   includes_count: array<string,int>,
     *   excludes_count: array<string,int>
     * }
     */
    public function evaluate(array $inputs, DatasetVersion $dv): array
    {
        $ctx = $this->normalizeInputs($inputs);

        // Load systems and rules for this dataset
        $systems = System::query()->get(['id','code','name'])->keyBy('id');
        $rules   = Rule::query()
            ->where('dataset_version_id', $dv->id)
            ->orderByDesc('priority')
            ->get(['id','system_id','rule_type','conditions','reason']);

        // Initialize all systems as viable until proven otherwise
        $perSystem     = [];
        $includesCount = [];
        $excludesCount = [];

        foreach ($systems as $sysId => $sys) {
            $perSystem[$sys->code]     = ['ok' => true, 'failed' => []];
            $includesCount[$sys->code] = 0;
            $excludesCount[$sys->code] = 0;
        }

        foreach ($rules as $rule) {
            $sys = $systems[$rule->system_id] ?? null;
            if (!$sys) {
                continue;
            }

            $conditions = $this->decode($rule->conditions);

            // Treat null or {} conditions as "always matches".
            $matches = $this->matches($conditions, $ctx);

            if (!$matches) {
                continue;
            }

            if ($rule->rule_type === 'include') {
                $includesCount[$sys->code] = ($includesCount[$sys->code] ?? 0) + 1;
                // We keep default as ok=true; include is informative.
                continue;
            }

            if ($rule->rule_type === 'exclude') {
                $excludesCount[$sys->code] = ($excludesCount[$sys->code] ?? 0) + 1;
                $perSystem[$sys->code]['ok'] = false;
                $perSystem[$sys->code]['failed'][] = [
                    'reason'  => $rule->reason ?: 'Rule excluded this system.',
                    'rule_id' => $rule->id,
                ];
            }
        }

        return [
            'per_system'      => $perSystem,
            'includes_count'  => $includesCount,
            'excludes_count'  => $excludesCount,
        ];
    }

    private function decode($json): array
    {
        if (is_array($json)) return $json;
        if (!$json) return [];
        try {
            $arr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($arr) ? $arr : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Normalize wizard inputs for consistent matching.
     */
    private function normalizeInputs(array $in): array
    {
        $out = $in;

        foreach (['residential_type','storage_type','ber_rating'] as $k) {
            if (isset($out[$k]) && is_string($out[$k])) {
                $out[$k] = mb_strtolower($out[$k]);
            }
        }

        // Ensure machinery is an array of strings
        $mach = $out['machinery'] ?? [];
        if (!is_array($mach)) $mach = [];
        $mach = array_values(array_unique(array_map(fn($v) => is_string($v) ? mb_strtolower($v) : $v, $mach)));
        $out['machinery'] = $mach;

        // Coerce numbers
        foreach ([
            'stories','height_m','res_units','commercial_units','storage_space_m2',
            'tower_crane_capacity_t','telescopic_crane_capacity_t','telehandler_capacity_t',
        ] as $numKey) {
            if (isset($out[$numKey])) {
                $out[$numKey] = is_numeric($out[$numKey]) ? $out[$numKey] + 0 : null;
            }
        }

        // Boolean
        if (array_key_exists('has_commercial', $out)) {
            $out['has_commercial'] = (bool) $out['has_commercial'];
        }

        return $out;
    }

    /**
     * Evaluate a rule's conditions object against the context.
     * Supported operators:
     *   eq, ne, in, not_in, lte, gte, contains, contains_any, contains_all
     */
    private function matches(array $conditions, array $ctx): bool
    {
        if ($conditions === []) {
            // Unconditional: matches everything
            return true;
        }

        foreach ($conditions as $key => $cond) {
            // Ignore non-input keys that sometimes appear in imported data
            if (in_array($key, ['system_code','value','constraints'], true)) {
                continue;
            }

            $lhs = $ctx[$key] ?? null;

            if (is_array($cond)) {
                foreach ($cond as $op => $rhs) {
                    if (!$this->matchOne($lhs, $op, $rhs)) {
                        return false;
                    }
                }
            } else {
                // Shorthand equality: { key: "value" }
                if (!$this->matchOne($lhs, 'eq', $cond)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function matchOne($lhs, string $op, $rhs): bool
    {
        switch ($op) {
            case 'eq':
                return $lhs === $rhs;
            case 'ne':
                return $lhs !== $rhs;
            case 'in':
                return is_array($rhs) ? in_array($lhs, $rhs, true) : false;
            case 'not_in':
                return is_array($rhs) ? !in_array($lhs, $rhs, true) : true;
            case 'lte':
                return (is_numeric($lhs) && is_numeric($rhs)) ? ($lhs <= $rhs) : false;
            case 'gte':
                return (is_numeric($lhs) && is_numeric($rhs)) ? ($lhs >= $rhs) : false;
            case 'contains':
                // array contains scalar
                return is_array($lhs) ? in_array($rhs, $lhs, true) : false;
            case 'contains_any':
                return is_array($lhs) && is_array($rhs)
                    ? (bool) array_intersect($lhs, $rhs)
                    : false;
            case 'contains_all':
                if (!is_array($lhs) || !is_array($rhs)) return false;
                foreach ($rhs as $needle) {
                    if (!in_array($needle, $lhs, true)) return false;
                }
                return true;
            default:
                // Unknown operator -> fail-safe to false so bad data doesn't match unexpectedly
                return false;
        }
    }
}
