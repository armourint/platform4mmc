<?php

namespace App\Services\DST;

use App\Models\DatasetVersion;
use App\Models\Rule;
use Illuminate\Support\Arr;

/**
 * ViabilityEvaluator
 *
 * Usage:
 *   $result = app(ViabilityEvaluator::class)->evaluate($inputs, $datasetVersion);
 *
 * $inputs is the normalized array from the Viability Wizard, e.g.:
 * [
 *   'residential_type'       => 'low|medium|high',
 *   'storage_types'          => ['on-site','off-site'],
 *   'machinery'              => ['tower_crane','telescopic_crane','telehandler'],
 *   'truck_types'            => ['flatbed_truck','flatbed_a_frame'],
 *   'panel_height_band'      => '<=3.0m' | '>3.0m',
 *   'max_frame_length_band'  => '<=12.0m' | '>12.0m',
 *   'max_frame_width_band'   => '<=3.2m' | '>3.2m',
 *   // legacy numeric proxies also OK: panel_height_m, max_frame_length_m, max_frame_width_m
 * ]
 */
class ViabilityEvaluator
{
    /**
     * Evaluate rules for a given dataset version.
     *
     * Returns:
     * [
     *   'summary' => [
     *       'include_count' => int,
     *       'exclude_count' => int,
     *       'status'        => 'OK'|'Attention'|'Excluded', // global status (simple heuristic)
     *   ],
     *   'systems' => [
     *       'BLOCK' => ['status' => 'included'|'excluded', 'reasons' => ['...','...']],
     *       'LGS'   => ['status' => 'included'|'excluded', 'reasons' => [...]],
     *       ...
     *   ],
     *   'matched_rules' => [
     *       'BLOCK' => [ ['id'=>..., 'type'=>'exclude', 'reason'=>'...'], ... ],
     *       ...
     *   ],
     * ]
     */
    public function evaluate(array $inputs, DatasetVersion $dv): array
    {
        // Fetch all rules for this dataset version.
        // Do NOT explicitly select a non-existent 'conditions' column.
        $rules = Rule::query()
            ->where('dataset_version_id', $dv->id)
            ->orderByDesc('priority')
            ->get(); // conditions_json is exposed as $rule->conditions via your accessor

        // Group rules by system_code
        $bySystem = [];
        foreach ($rules as $rule) {
            $code = $rule->system_code ?: 'UNKNOWN';
            $bySystem[$code][] = $rule;
        }

        $systemsOut     = [];
        $matchedOut     = [];
        $includeCount   = 0;
        $excludeCount   = 0;

        foreach ($bySystem as $systemCode => $systemRules) {
            $reasons = [];
            $excluded = false;

            // Our current importer generates EXCLUDE rules for unsupported facets.
            // If any EXCLUDE rule's conditions match the inputs => system excluded.
            foreach ($systemRules as $rule) {
                $conditions = $rule->conditions ?? []; // virtual attribute maps to conditions_json
                if ($this->conditionsMatch($conditions, $inputs)) {
                    if (strtolower($rule->rule_type) === 'exclude') {
                        $excluded = true;
                    }
                    $reasons[] = $rule->reason ?: 'Rule matched';
                    $matchedOut[$systemCode][] = [
                        'id'     => $rule->id,
                        'type'   => strtolower($rule->rule_type),
                        'reason' => $rule->reason,
                    ];
                }
            }

            if ($excluded) {
                $systemsOut[$systemCode] = [
                    'status'  => 'excluded',
                    'reasons' => array_values(array_unique($reasons)),
                ];
                $excludeCount++;
            } else {
                $systemsOut[$systemCode] = [
                    'status'  => 'included',
                    'reasons' => array_values(array_unique($reasons)), // might be empty
                ];
                $includeCount++;
            }
        }

        // If there are no rules at all for this dataset, treat as all-included (defensive)
        if (empty($systemsOut)) {
            $systemsOut['(no-rules)'] = [
                'status'  => 'included',
                'reasons' => ['No rules present in dataset.'],
            ];
            $includeCount = 1;
        }

        // Simple global status heuristic for the chip:
        // - If all excluded => 'Excluded'
        // - If some excluded and some included => 'Attention'
        // - If none excluded => 'OK'
        $globalStatus = 'OK';
        if ($excludeCount > 0 && $includeCount > 0) {
            $globalStatus = 'Attention';
        } elseif ($excludeCount > 0 && $includeCount === 0) {
            $globalStatus = 'Excluded';
        }

        return [
            'summary' => [
                'include_count' => $includeCount,
                'exclude_count' => $excludeCount,
                'status'        => $globalStatus,
            ],
            'systems'       => $systemsOut,
            'matched_rules' => $matchedOut,
        ];
    }

    /**
     * Determine if a conditions JSON object matches the given inputs.
     *
     * Supported operators:
     *  - Scalars: eq, ne, in, not_in, gte, lte
     *  - Arrays:  contains, contains_any, contains_all
     *
     * The $conditions should be a flat JSON object where each key corresponds
     * to an input key, and its value is either a scalar test or an object of operators.
     *
     * Examples:
     *  { "residential_type": {"eq":"low"} }
     *  { "storage_types": {"contains":"off-site"} }
     *  { "machinery": {"contains_any":["tower_crane","telescopic_crane"]} }
     *  { "panel_height_band": {"eq":">3.0m"} }
     */
    protected function conditionsMatch(?array $conditions, array $inputs): bool
    {
        $conditions = $conditions ?? [];

        foreach ($conditions as $key => $test) {
            // If the rule references an input we don't have, treat it as non-match.
            if (!Arr::has($inputs, $key)) {
                return false;
            }

            $inputVal = Arr::get($inputs, $key);

            // If the condition is a plain scalar, treat as eq
            if (!is_array($test)) {
                if (!$this->opEq($inputVal, $test)) {
                    return false;
                }
                continue;
            }

            // Otherwise it's an operator set
            foreach ($test as $op => $expected) {
                $op = strtolower((string) $op);

                // Array-ops expect the input to be an array
                if (in_array($op, ['contains','contains_any','contains_all'], true)) {
                    $inputArr = is_array($inputVal) ? $inputVal : [$inputVal];
                    if ($op === 'contains') {
                        if (!$this->opContains($inputArr, $expected)) return false;
                    } elseif ($op === 'contains_any') {
                        if (!$this->opContainsAny($inputArr, (array) $expected)) return false;
                    } elseif ($op === 'contains_all') {
                        if (!$this->opContainsAll($inputArr, (array) $expected)) return false;
                    }
                    continue;
                }

                // Scalar ops
                switch ($op) {
                    case 'eq':
                        if (!$this->opEq($inputVal, $expected)) return false;
                        break;
                    case 'ne':
                        if ($this->opEq($inputVal, $expected)) return false;
                        break;
                    case 'in':
                        if (!$this->opIn($inputVal, (array) $expected)) return false;
                        break;
                    case 'not_in':
                        if ($this->opIn($inputVal, (array) $expected)) return false;
                        break;
                    case 'gte':
                        if (!$this->opGte($inputVal, $expected)) return false;
                        break;
                    case 'lte':
                        if (!$this->opLte($inputVal, $expected)) return false;
                        break;
                    default:
                        // Unknown operator -> treat as non-match to be safe
                        return false;
                }
            }
        }

        return true;
    }

    /* ---------------------- Operator helpers ---------------------- */

    protected function opEq($actual, $expected): bool
    {
        // Normalize strings case-insensitively for enums like 'low', 'on-site'
        if (is_string($actual) && is_string($expected)) {
            return strcmp(mb_strtolower($actual), mb_strtolower($expected)) === 0;
        }
        return $actual === $expected;
    }

    protected function opIn($actual, array $set): bool
    {
        if (is_string($actual)) {
            $set = array_map(fn($v) => is_string($v) ? mb_strtolower($v) : $v, $set);
            return in_array(mb_strtolower($actual), $set, true);
        }
        return in_array($actual, $set, true);
    }

    protected function opGte($actual, $threshold): bool
    {
        $a = is_numeric($actual) ? (float) $actual : null;
        $t = is_numeric($threshold) ? (float) $threshold : null;
        return $a !== null && $t !== null && $a >= $t;
    }

    protected function opLte($actual, $threshold): bool
    {
        $a = is_numeric($actual) ? (float) $actual : null;
        $t = is_numeric($threshold) ? (float) $threshold : null;
        return $a !== null && $t !== null && $a <= $t;
    }

    protected function opContains(array $haystack, $needle): bool
    {
        if (is_string($needle)) {
            foreach ($haystack as $h) {
                if (is_string($h) && mb_strtolower($h) === mb_strtolower($needle)) {
                    return true;
                }
            }
            return false;
        }
        return in_array($needle, $haystack, true);
    }

    protected function opContainsAny(array $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if ($this->opContains($haystack, $n)) {
                return true;
            }
        }
        return false;
    }

    protected function opContainsAll(array $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (!$this->opContains($haystack, $n)) {
                return false;
            }
        }
        return true;
    }
}
