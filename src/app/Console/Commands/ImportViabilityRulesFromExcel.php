<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\Rule;
use App\Models\System;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportViabilityRulesFromExcel extends Command
{
    protected $signature = 'mmc:import-viability-rules
        {--path= : Path to mmc_viability_data.xlsx}
        {--sheet= : Sheet name (optional; defaults to first sheet)}
        {--dataset-version= : Dataset version label (e.g. v2025.09)}
        {--module=viability : Module name to attach rules to (default: viability)}
        {--priority=10 : Priority to assign to created rules}
        {--reason-prefix= : Optional text prefixed to auto-generated “excluded for …” reason}
        {--reset : Delete existing rules for this dataset/module before importing}
        {--dry-run : Parse and report, but don’t write to DB}
    ';

    protected $description = 'Import system viability rules from mmc_viability_data.xlsx (creates EXCLUDE rules for FALSE flags).';

    public function handle(): int
    {
        $path      = $this->option('path') ?: base_path('mmc_viability_data.xlsx');
        $sheetName = $this->option('sheet');
        $version   = $this->option('dataset-version');
        $module    = (string)($this->option('module') ?: 'viability');
        $priority  = (int) $this->option('priority');
        $prefix    = (string)($this->option('reason-prefix') ?: '');
        $dryRun    = (bool) $this->option('dry-run');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }
        if (!$version) {
            $this->error('Missing required option: --dataset-version');
            return self::FAILURE;
        }

        $dataset = DatasetVersion::firstOrCreate(
            ['module' => 'viability', 'version_label' => $version],
            ['status' => 'draft', 'payload' => []]
        );

        if ($this->option('reset')) {
            if ($dryRun) {
                $count = Rule::where('dataset_version_id', $dataset->id)
                    ->where('module', $module)->count();
                $this->warn("DRY-RUN Reset: would delete {$count} rule(s) for dataset {$version} / module {$module}.");
            } else {
                $deleted = Rule::where('dataset_version_id', $dataset->id)
                    ->where('module', $module)->delete();
                $this->info("Reset: deleted {$deleted} rule(s) for dataset {$version} / module {$module}.");
            }
        }

        // Load excel
        $sheets = Excel::toArray(null, $path);
        if (empty($sheets)) {
            $this->error('No sheets found.');
            return self::FAILURE;
        }
        $active = null;
        if ($sheetName) {
            // Try to find by name — Excel::toArray doesn’t return names,
            // so we just pick the first sheet (caller should ensure the
            // requested sheet is first if needed).
            $this->warn('Note: Maatwebsite Excel::toArray() does not preserve sheet names; using first sheet.');
        }
        $active = $sheets[0];

        if (empty($active) || !is_array($active) || count($active) < 2) {
            $this->error('Active sheet appears empty (need at least header + 1 row).');
            return self::FAILURE;
        }

        // Normalize headers
        $headerRow = array_shift($active);
        $norm      = fn (?string $v) => $this->normalizeHeader($v);
        $headers   = array_map($norm, $headerRow);

        // Build column index map using flexible header detection
        $idx = [
            'mmc_method' => $this->findHeaderIndex($headers, [
                'mmc_method', 'mmc', 'method', 'system', 'mmc_method---'
            ]),

            // Boolean viability flags (treated as tri-state)
            'low_rise'            => $this->findHeaderIndexLike($headers, '/\blow\b.*\brise\b/i'),
            'medium_rise'         => $this->findHeaderIndexLike($headers, '/\bmedium\b.*\brise\b/i'),
            'high_rise'           => $this->findHeaderIndexLike($headers, '/\bhigh\b.*\brise\b/i'),
            'on_site_storage'     => $this->findHeaderIndexLike($headers, '/on.*site.*storage/i'),
            'off_site_storage'    => $this->findHeaderIndexLike($headers, '/off.*site.*storage/i'),
            'tower_crane'         => $this->findHeaderIndexLike($headers, '/tower.*crane/i'),
            'telescopic_crane'    => $this->findHeaderIndexLike($headers, '/telescopic.*crane/i'),
            'telehandler_crane'   => $this->findHeaderIndexLike($headers, '/telehandler.*crane/i'),
            'flatbed_truck'       => $this->findHeaderIndexLike($headers, '/flatbed.*truck/i'),
            'flatbed_a_frame'     => $this->findHeaderIndexLike($headers, '/flatbed.*a.*frame/i'),

            // Numeric constraints (optional)
            'max_panel_height_m'        => $this->findHeaderIndexLike($headers, '/max.*panel.*height/i'),
            'max_frame_length_m'        => $this->findHeaderIndexLike($headers, '/max.*frame.*length/i'),
            'max_frame_width_lt_3_2_m'  => $this->findHeaderIndexLike($headers, '/max.*width.*(lt|<).*(3[.,]2|3\.2)/i'),
            'max_frame_width_gt_3_2_m'  => $this->findHeaderIndexLike($headers, '/max.*width.*(gt|>).*(3[.,]2|3\.2)/i'),
        ];

        if ($idx['mmc_method'] === null) {
            $this->error('Missing required column: MMC Method (cannot resolve system).');
            return self::FAILURE;
        }

        $made  = 0;
        $skips = 0;
        $rowsParsed = 0;

        $aliasStats = [];

        // Iterate rows
        foreach ($active as $r) {
            $rowsParsed++;

            $mmcLabel = (string)($r[$idx['mmc_method']] ?? '');
            [$systemId, $systemCode] = $this->resolveSystemIdAndCode($mmcLabel);

            if (!$systemId) {
                $this->warn("Skip row #{$rowsParsed}: unknown system for MMC Method '{$mmcLabel}'");
                $skips++;
                continue;
            }
            $aliasStats[$systemCode] = true;

            // Pull tri-state flags
            $flags = $this->extractFlagValues($r, $idx);

            // Special: Telescopic Crane empty cells for LGS/ICF must be bypassed (no rule)
            if (array_key_exists('telescopic_crane', $flags)) {
                $isEmpty = is_null($flags['telescopic_crane']);
                if ($isEmpty && in_array(strtoupper($systemCode), ['LGS', 'ICF'], true)) {
                    unset($flags['telescopic_crane']); // bypass
                }
            }

            // Optional numeric constraints
            $constraints = $this->extractConstraints($r, $idx);

            // We only WRITE rules for explicit FALSE (meaning “excluded”)
            $toWrite = [];
            foreach ($flags as $key => $value) {
                if ($value === false) {
                    $toWrite[] = $key;
                }
            }

            if (empty($toWrite)) {
                continue;
            }

            if ($dryRun) {
                foreach ($toWrite as $flag) {
                    $this->line("DRY-RUN would EXCLUDE {$systemCode} for {$flag}");
                }
                $made += count($toWrite);
                continue;
            }

            // Persist
            DB::transaction(function () use ($dataset, $module, $priority, $prefix, $systemId, $systemCode, $toWrite, $constraints, &$made) {
                foreach ($toWrite as $flag) {
                    $payload = [
                        'system_code'  => $systemCode,
                        'flag'         => $flag,
                        'value'        => false,
                    ];
                    if (!empty($constraints)) {
                        $payload['constraints'] = $constraints;
                    }

                    Rule::create([
                        'dataset_version_id' => $dataset->id,
                        'module'             => $module,
                        'system_id'          => $systemId,
                        'system_code'        => $systemCode,
                        'rule_type'          => 'exclude',
                        'conditions_json'    => $payload,
                        'reason'             => trim(($prefix ? "{$prefix} " : '') . "{$systemCode} excluded for " . $this->prettyFlag($flag)),
                        'priority'           => $priority,
                    ]);
                    $made++;
                }
            });
        }

        $codes = implode(', ', array_keys($aliasStats));
        $this->info("Parsed rows: {$rowsParsed}. Wrote EXCLUDE rules: {$made}. Systems seen: {$codes}");

        return self::SUCCESS;
    }

    /* ------------------------------ Helpers ------------------------------ */

    protected function normalizeHeader(?string $v): string
    {
        $v = (string)$v;
        $v = trim(strtolower($v));
        $v = str_replace(['—', '–', '−'], '-', $v); // normalize dashes
        $v = preg_replace('/\s+/', ' ', $v);        // collapse spaces
        $v = preg_replace('/[^a-z0-9\.\-\s_]/i', '_', $v);
        $v = str_replace(' ', '_', $v);
        return $v;
    }

    protected function findHeaderIndex(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $c) {
            $key = $this->normalizeHeader($c);
            $i = array_search($key, $headers, true);
            if ($i !== false) return $i;
        }
        return null;
    }

    protected function findHeaderIndexLike(array $headers, string $pattern): ?int
    {
        foreach ($headers as $i => $h) {
            if (preg_match($pattern, $h)) return $i;
        }
        return null;
    }

    protected function resolveSystemIdAndCode(?string $mmcMethod): array
    {
        $label = strtoupper(trim((string)$mmcMethod));
        if ($label === '') return [null, null];

        // alias map: label -> code
        $aliases = [
            'CONCRETE BLOCK'         => 'BLOCK',
            'BLOCK'                  => 'BLOCK',
            'MASONRY'                => 'BLOCK',
            'TIMBER FRAME'           => 'TF',
            'TIMBERFRAME'            => 'TF',
            'TF'                     => 'TF',
            'LGS'                    => 'LGS',
            'LIGHT GAUGE STEEL'      => 'LGS',
            'LIGHT-GAUGE STEEL'      => 'LGS',
            'LIGHT GAUGE STEEL (LGS)'=> 'LGS',
            'ICF'                    => 'ICF',
            'INSULATED CONCRETE FORMWORK' => 'ICF',
        ];

        // Handle labels like "LGS (Light Gauge Steel)" etc.
        if (preg_match('/\bLGS\b/i', $label)) $aliases[$label] = 'LGS';
        if (preg_match('/\bTF\b/i', $label))  $aliases[$label] = 'TF';
        if (preg_match('/\bICF\b/i', $label)) $aliases[$label] = 'ICF';

        $code = $aliases[$label] ?? $label;

        // try by code
        $system = System::where('code', $code)->first();
        if (!$system) {
            // try exact name, then LIKE
            $system = System::whereRaw('upper(name) = ?', [$label])->first()
                   ?: System::whereRaw('upper(name) like ?', ['%'.$label.'%'])->first();
        }

        return [$system?->id, $system?->code ?? $code];
    }

    protected function coerceTri($val): ?bool
    {
        // 1) direct booleans
        if (is_bool($val)) {
            return $val; // true/false as-is
        }

        // 2) numeric (Excel may coerce TRUE/FALSE to 1/0)
        if (is_int($val) || is_float($val)) {
            if ($val === 1 || $val === 1.0) return true;
            if ($val === 0 || $val === 0.0) return false;
            // other numerics treated as truthy
            return $val ? true : null;
        }

        // 3) strings and everything else
        if ($val === null) return null;
        $v = trim((string)$val);
        if ($v === '') return null;

        $vu = strtoupper($v);
        // common true-ish
        if (in_array($vu, ['Y','YES','TRUE','T','1'], true)) return true;
        // common false-ish
        if (in_array($vu, ['N','NO','FALSE','F','0'], true)) return false;

        return null;
    }

    protected function extractFlagValues(array $row, array $idx): array
    {
        $out = [];
        $keys = [
            'low_rise','medium_rise','high_rise',
            'on_site_storage','off_site_storage',
            'tower_crane','telescopic_crane','telehandler_crane',
            'flatbed_truck','flatbed_a_frame',
        ];
        foreach ($keys as $k) {
            if ($idx[$k] !== null) {
                $out[$k] = $this->coerceTri($row[$idx[$k]] ?? null);
            }
        }
        return $out;
    }

    protected function extractConstraints(array $row, array $idx): array
    {
        $c = [];
        $num = function ($v) {
            if ($v === null || $v === '') return null;
            $v = str_replace(',', '.', (string)$v);
            return is_numeric($v) ? (float)$v : null;
        };

        $map = [
            'max_panel_height_m',
            'max_frame_length_m',
            'max_frame_width_lt_3_2_m',
            'max_frame_width_gt_3_2_m',
        ];

        foreach ($map as $key) {
            if ($idx[$key] !== null) {
                $val = $num($row[$idx[$key]] ?? null);
                if ($val !== null) $c[$key] = $val;
            }
        }
        return $c;
    }

    protected function prettyFlag(string $flag): string
    {
        $map = [
            'low_rise'                  => 'Low Rise',
            'medium_rise'               => 'Medium Rise',
            'high_rise'                 => 'High Rise',
            'on_site_storage'           => 'On Site Storage',
            'off_site_storage'          => 'Off Site Storage',
            'tower_crane'               => 'Tower Crane',
            'telescopic_crane'          => 'Telescopic Crane',
            'telehandler_crane'         => 'Telehandler Crane',
            'flatbed_truck'             => 'Flatbed Truck',
            'flatbed_a_frame'           => 'Flatbed A Frame',
        ];
        return $map[$flag] ?? $flag;
    }
}