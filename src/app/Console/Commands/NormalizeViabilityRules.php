<?php

namespace App\Console\Commands;

use App\Models\Rule;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class NormalizeViabilityRules extends Command
{
    protected $signature = 'mmc:normalize-viability-rules
        {--dry-run : Show changes without saving}
        {--dataset= : Limit to dataset_version_id}';

    protected $description = 'Normalize viability rules conditions to match ViabilityWizard inputs.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $limitDv = $this->option('dataset') ? (int) $this->option('dataset') : null;

        $q = Rule::query();
        if ($limitDv) $q->where('dataset_version_id', $limitDv);

        $rules = $q->get();

        $changed = 0;

        foreach ($rules as $rule) {
            $orig = $rule->conditions;
            $cond = $this->decode($orig);

            // Strip junk keys that should never be used for matching
            unset($cond['system_code'], $cond['value']);

            // Pull up anything inside "constraints" -> currently ignored by evaluator; leave as-is but not part of conditions
            if (isset($cond['constraints']) && is_array($cond['constraints'])) {
                // keep in DB but not as matching conditions
                // (no-op; just ensure it doesn't block matching)
            }

            // Flag-to-proper-field conversions
            if (isset($cond['flag'])) {
                $flag = $cond['flag'];
                unset($cond['flag']); // remove flag key

                $cond = $this->applyFlagConversion($cond, $flag);
            }

            // Fix telehandler naming if present
            array_walk_recursive($cond, function (&$v, $k) {
                if (is_string($v) && $v === 'telehandler_crane') {
                    $v = 'telehandler';
                }
            });

            // If we didn’t change anything, continue
            if ($cond === $this->decode($orig)) {
                continue;
            }

            $changed++;

            if ($dry) {
                $this->line("Rule #{$rule->id} would change:");
                $this->line('  from: ' . json_encode($this->decode($orig), JSON_UNESCAPED_SLASHES));
                $this->line('    to: ' . json_encode($cond, JSON_UNESCAPED_SLASHES));
                continue;
            }

            $rule->conditions = $cond;
            $rule->save();
            $this->line("Rule #{$rule->id} normalized.");
        }

        $this->info($dry
            ? "Done (dry run). {$changed} rules would be updated."
            : "Done. {$changed} rules updated.");

        return self::SUCCESS;
    }

    private function decode($json): array
    {
        if (is_array($json)) return $json;
        if (!$json) return [];
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function applyFlagConversion(array $cond, string $flag): array
    {
        $flag = mb_strtolower($flag);

        // Residential type flags
        if (in_array($flag, ['low_rise','medium_rise','high_rise'], true)) {
            $map = [
                'low_rise'    => 'low',
                'medium_rise' => 'medium',
                'high_rise'   => 'high',
            ];
            $cond['residential_type']['eq'] = $map[$flag];
            return $cond;
        }

        // Storage flags
        if ($flag === 'on_site_storage') {
            $cond['storage_type']['eq'] = 'on-site';
            return $cond;
        }
        if ($flag === 'off_site_storage') {
            $cond['storage_type']['eq'] = 'off-site';
            return $cond;
        }

        // Machinery flags
        if (in_array($flag, ['tower_crane','telescopic_crane','telehandler','telehandler_crane'], true)) {
            $needle = $flag === 'telehandler_crane' ? 'telehandler' : $flag;
            $cond['machinery']['contains'] = $needle;
            return $cond;
        }

        // Delivery method flags (no corresponding input in wizard) -> drop condition
        if (in_array($flag, ['flatbed_truck','flatbed_a_frame'], true)) {
            // Intentionally ignore: no matching input exists
            return $cond;
        }

        // Unknown flag -> leave as-is (but since we removed 'flag', nothing added)
        return $cond;
    }
}
