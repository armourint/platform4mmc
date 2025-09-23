<?php

namespace App\Console\Commands;

use App\Models\EpdProduct;
use App\Models\ProductEpdMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ImportEPDProductsFromExcel extends Command
{
    protected $signature = 'mmc:import-products
        {--path= : Path to the EPD Excel file}
        {--sheet= : Sheet name (default: first sheet)}
        {--reset : Truncate epd_products and product_epd_metrics before import}
        {--dry : Parse only; don\'t write}';

    protected $description = 'Import EPD product catalog (epd_products + product_epd_metrics)';

    public function handle(): int
    {
        $path = $this->option('path');
        if (!$path || !is_file($path)) {
            $this->error('File not found: ' . ($path ?: '(none)'));
            return self::FAILURE;
        }

        // Optional reset (IMPORTANT: metrics first, then products due to FK)
        if ($this->option('reset')) {
            DB::beginTransaction();
            try {
                if (Schema::hasTable('product_epd_metrics')) {
                    DB::statement('TRUNCATE TABLE product_epd_metrics');
                }
                if (Schema::hasTable('epd_products')) {
                    DB::statement('TRUNCATE TABLE epd_products');
                }
                DB::commit();
                $this->info('Reset complete: truncated product_epd_metrics and epd_products.');
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->warn('Reset failed gracefully; falling back to delete cascade… '.$e->getMessage());
                ProductEpdMetric::query()->delete();
                EpdProduct::query()->delete();
                $this->info('Reset via delete() complete.');
            }
        }

        // Load the workbook
        $sheets = Excel::toArray(null, $path);
        if (empty($sheets)) {
            $this->error('No sheets found in workbook.');
            return self::FAILURE;
        }

        $sheetName = $this->option('sheet');
        $active = $sheets[0];
        if ($sheetName !== null) {
            foreach ($sheets as $candidate) {
                // maatwebsite returns only arrays, so we just pick the first with non-empty header
                if (is_array($candidate) && !empty($candidate)) {
                    $active = $candidate;
                    break;
                }
            }
        }

        if (count($active) < 2) {
            $this->warn('Sheet has no data rows.');
            return self::SUCCESS;
        }

        // Normalize headers
        $rawHeader = array_map(fn($h) => is_string($h) ? trim($h) : (string)$h, $active[0]);
        $headerMap = [];
        foreach ($rawHeader as $i => $label) {
            $key = strtolower(preg_replace('/\s+/', ' ', str_replace(['–','—','  '], ['-','-',' '], $label)));
            $headerMap[$key] = $i;
        }

        // handy getter
        $get = function(array $row, string $label) use ($headerMap) {
            $k = strtolower($label);
            return array_key_exists($k, $headerMap) ? ($row[$headerMap[$k]] ?? null) : null;
        };

        // column aliases (based on the CSV of column titles you shared)
        $aliases = [
            'uuid'                              => 'uuid',
            'country code'                      => 'country_code',
            'country'                           => 'country',
            'product category'                  => 'category',
            'product subcategory'               => 'subcategory',
            'product owner'                     => 'owner',
            'product name'                      => 'name',
            'epd registration authority'        => 'reg_auth',
            'registration number'               => 'reg_number',
            'reference year'                    => 'reference_year',
            'valid until'                       => 'valid_until',

            'technology description'            => 'technology_description',
            'technical purpose'                 => 'technical_purpose',
            'general comment'                   => 'general_comment',
            'use advice'                        => 'use_advice',
            'lca methodology report'            => 'lca_methodology_report',
            'data quality management'           => 'data_quality_management',
            'type of review'                    => 'type_of_review',

            'mass per du [kg]'                  => 'mass_per_du_kg',
            'weight per m2 [kg]'                => 'weight_per_m2_kg',
            'product thickness [m]'             => 'thickness_m',
            'product length [m]'                => 'length_m',
            'product width [m]'                 => 'width_m',
            'product height [m]'                => 'height_m',
            'area [m²]'                         => 'area_m2',
            'volume [m³]'                       => 'volume_m3',
            'specific surface [m²/kg]'          => 'specific_surface_m2_per_kg',
            'density [kg/m3]'                   => 'density_kg_m3',
            'thermal conductivity [w/mk]'       => 'thermal_conductivity_w_mk',
            'thermal resistance, r [m²k/w]'     => 'thermal_resistance_m2k_w',
            'density [kg/litre]'                => 'density_kg_litre',
            'coverage [m² per litre]'           => 'coverage_m2_per_litre',

            'declared amount'                   => 'declared_amount',
            'resulting amount'                  => 'resulting_amount',
            'reference property'                => 'reference_property',
            'reference unit'                    => 'reference_unit',
            'category unit'                     => 'category_unit',

            // GWP summary fields
            'global warming potential (gwp) [kg co2e] - a1-a3 (per category unit)' => 'gwp_a1a3_catunit',
            'global warming potential (gwp) [kg co2e] - a1-a3 (sum)'               => 'gwp_a1a3_sum',
            'global warming potential (gwp) [kg co2e] - a1'                        => 'gwp_a1',
            'global warming potential (gwp) [kg co2e] - a2'                        => 'gwp_a2',
            'global warming potential (gwp) [kg co2e] - a3'                        => 'gwp_a3',

            'global warming potential - total (gwp-total) [kg co2e] - a1'          => 'gwp_total_a1',
            'global warming potential - total (gwp-total) [kg co2e] - a2'          => 'gwp_total_a2',
            'global warming potential - total (gwp-total) [kg co2e] - a3'          => 'gwp_total_a3',

            'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a1' => 'gwp_luluc_a1',
            'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a2' => 'gwp_luluc_a2',
            'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a3' => 'gwp_luluc_a3',
            'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a1-a3 (sum)' => 'gwp_luluc_a1a3_sum',

            'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a1'    => 'gwp_biogenic_a1',
            'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a2'    => 'gwp_biogenic_a2',
            'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a3'    => 'gwp_biogenic_a3',
            'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a1-a3 (sum)' => 'gwp_biogenic_a1a3_sum',

            'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a1'  => 'gwp_fossil_a1',
            'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a2'  => 'gwp_fossil_a2',
            'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a3'  => 'gwp_fossil_a3',
            'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a1-a3 (sum)' => 'gwp_fossil_a1a3_sum',
        ];

        $rows = array_slice($active, 1);
        $created = 0;

        DB::transaction(function () use ($rows, $aliases, $get, &$created) {
            foreach ($rows as $r) {
                // Skip totally empty lines
                if (!is_array($r) || count(array_filter($r, fn($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                // Build payload for epd_products
                $payload = [];
                foreach ($aliases as $header => $field) {
                    $val = $get($r, $header);
                    if ($val === '' || $val === null) {
                        $payload[$field] = null;
                        continue;
                    }
                    // Cast some numeric-ish fields
                    if (preg_match('/^(mass_|weight_|thickness_|length_|width_|height_|area_|volume_|specific_surface_|density_|thermal_|coverage_|declared_amount|resulting_amount|gwp_)/', $field)) {
                        $payload[$field] = is_numeric($val) ? (float)$val : (float)str_replace([','], [''], (string)$val);
                    } elseif (in_array($field, ['reference_year'])) {
                        $payload[$field] = is_numeric($val) ? (int)$val : null;
                    } elseif ($field === 'valid_until') {
                        // accept yyyy-mm-dd or dd/mm/yyyy etc.
                        try {
                            $payload[$field] = date('Y-m-d', strtotime((string)$val));
                        } catch (\Throwable $e) {
                            $payload[$field] = null;
                        }
                    } else {
                        $payload[$field] = is_string($val) ? trim($val) : $val;
                    }
                }

                // Must have a minimal identity to store (owner+name or reg_number)
                if (empty($payload['name']) && empty($payload['reg_number'])) {
                    continue;
                }

                /** @var EpdProduct $product */
                $product = EpdProduct::create($payload);
                $created++;

                // Optional metrics (populate if present)
                $metricDefs = [
                    // module => [ indicator => header-key ]
                    'A1'    => [
                        'GWP-total'  => 'global warming potential - total (gwp-total) [kg co2e] - a1',
                        'GWP-luluc'  => 'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a1',
                        'GWP-biogenic'=> 'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a1',
                        'GWP-fossil' => 'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a1',
                    ],
                    'A2'    => [
                        'GWP-total'  => 'global warming potential - total (gwp-total) [kg co2e] - a2',
                        'GWP-luluc'  => 'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a2',
                        'GWP-biogenic'=> 'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a2',
                        'GWP-fossil' => 'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a2',
                    ],
                    'A3'    => [
                        'GWP-total'  => 'global warming potential - total (gwp-total) [kg co2e] - a3',
                        'GWP-luluc'  => 'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a3',
                        'GWP-biogenic'=> 'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a3',
                        'GWP-fossil' => 'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a3',
                    ],
                    'A1-A3' => [
                        'GWP'        => 'global warming potential (gwp) [kg co2e] - a1-a3 (sum)',
                        'GWP per CU' => 'global warming potential (gwp) [kg co2e] - a1-a3 (per category unit)',
                        'GWP-luluc'  => 'global warming potential - land use and land use change (gwp-luluc) [kg co2e] - a1-a3 (sum)',
                        'GWP-biogenic'=> 'global warming potential - biogenic (gwp-biogenic) [kg co2e] - a1-a3 (sum)',
                        'GWP-fossil' => 'global warming potential - fossil fuels (gwp-fossil) [kg co2e] - a1-a3 (sum)',
                    ],
                ];

                foreach ($metricDefs as $module => $indicators) {
                    foreach ($indicators as $indicator => $hdr) {
                        $v = $get($r, $hdr);
                        if ($v === '' || $v === null) continue;
                        $num = is_numeric($v) ? (float)$v : (float)str_replace([','], [''], (string)$v);
                        ProductEpdMetric::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'module'     => $module,
                                'indicator'  => $indicator,
                                'unit'       => $payload['category_unit'] ?? null,
                            ],
                            ['value' => $num]
                        );
                    }
                }
            }
        });

        $this->info("Imported {$created} product(s) into epd_products.");
        return self::SUCCESS;
    }
}
