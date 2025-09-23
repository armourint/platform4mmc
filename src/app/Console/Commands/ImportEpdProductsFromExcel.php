<?php

// app/Console/Commands/ImportEpdProductsFromExcel.php
namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportEpdProductsFromExcel extends Command
{
    protected $signature = 'mmc:import-products
        {--path= : Path to EPD workbook}
        {--sheet= : Sheet name (optional; default first)}
        {--reset : Truncate products table first}
    ';
    protected $description = 'Import EPD products from spreadsheet into products table.';

    public function handle(): int
    {
        if ($this->option('reset')) {
            $this->resetProducts();
        }
        
        $path = $this->option('path');
        if (!$path || !is_file($path)) { $this->error('Provide a valid --path'); return self::FAILURE; }

        if ($this->option('reset')) { Product::truncate(); $this->warn('Reset: truncated products table.'); }

        $sheets = Excel::toArray(null, $path);
        $rows = $sheets[0] ?? [];
        if ($this->option('sheet')) {
            // Best-effort: Excel::toArray() doesn’t expose names; assume first is correct for now
        }
        if (empty($rows)) { $this->error('No rows found.'); return self::FAILURE; }

        $header = array_map([$this,'norm'], array_shift($rows));
        $idx = [];
        foreach ($header as $i=>$h) { if ($h!=='') $idx[$h]=$i; }

        $map = $this->columnMap();

        $ins=0; $upd=0;
        DB::transaction(function() use($rows,$idx,$map,&$ins,&$upd){
            foreach ($rows as $r) {
                if (!count(array_filter($r, fn($v)=>trim((string)$v)!==''))) continue;

                [$payload,$extras] = $this->buildPayload($r,$idx,$map);

                // identity for upsert
                $unique = [
                    'epd_number'   => $payload['epd_number'] ?? null,
                    'manufacturer' => $payload['manufacturer'] ?? null,
                    'product_name' => $payload['product_name'] ?? null,
                ];
                if (!$unique['epd_number'] && !($unique['manufacturer'] && $unique['product_name'])) continue;

                $q = Product::query();
                if ($unique['epd_number']) $q->orWhere('epd_number',$unique['epd_number']);
                if ($unique['manufacturer'] && $unique['product_name']) {
                    $q->orWhere(fn($qq)=>$qq->where('manufacturer',$unique['manufacturer'])
                                            ->where('product_name',$unique['product_name']));
                }
                $existing = $q->first();

                if (!empty($extras)) $payload['extras'] = array_merge($existing?->extras ?? [], $extras);

                if ($existing) { $existing->fill($payload)->save(); $upd++; }
                else { Product::create($payload); $ins++; }
            }
        });

        $this->info("Products upserted. Inserted: $ins, Updated: $upd");
        return self::SUCCESS;
    }

    private function norm(?string $s): string
    {
        $s = strtolower((string)$s);
        $s = str_replace(["\u{2013}","\u{2014}"], '-', $s); // – —
        $s = str_replace(['co₂e','co?e'], 'co2e', $s);
        $s = str_replace(['(',')','[',']','{','}','/','\\','.'], ' ', $s);
        $s = preg_replace('/[^a-z0-9\-\s_]/', ' ', $s); // strip odd glyphs
        $s = preg_replace('/\s+/', ' ', $s);
        return trim(str_replace(' ', '_', $s));
    }

    private function columnMap(): array
    {
        return [
            // identity & taxonomy
            'uuid'                         => 'uuid',
            'country_code'                 => 'country_code',
            'country'                      => 'country',
            'product_category'             => 'category',
            'product_subcategory'          => 'subcategory',
            'product_owner'                => 'manufacturer',
            'product_name'                 => 'product_name',

            // program / meta
            'epd_registration_authority'   => 'epd_program',
            'registration_number'          => 'epd_number',
            'reference_year'               => 'reference_year',
            'valid_until'                  => 'valid_to',

            // units & amounts
            'reference_property'           => 'reference_property',
            'reference_unit'               => 'reference_unit',
            'category_unit'                => 'category_unit',
            'declared_amount'              => 'declared_amount',
            'resulting_amount'             => 'resulting_amount',

            // geometry & properties (meters as provided)
            'mass_per_du_kg'               => 'mass_per_du_kg',
            'weight_per_m2_kg'             => 'weight_per_m2_kg',
            'product_thickness_m'          => 'thickness_m',
            'product_length_m'             => 'length_m',
            'product_width_m'              => 'width_m',
            'product_height_m'             => 'height_m',
            'area_m2'                      => 'area_m2',
            'volume_m3'                    => 'volume_m3',
            'specific_surface_m2_kg'       => 'specific_surface_m2_per_kg',
            'density_kg_m3'                => 'density_kg_m3',
            'thermal_conductivity_w_mk'    => 'thermal_conductivity_w_mk',
            'thermal_resistance_r_m2k_w'   => 'thermal_resistance_m2k_w',
            'density_kg_litre'             => 'density_kg_litre',
            'coverage_m2_per_litre'        => 'coverage_m2_per_litre',

            // LCA: A1–A3 per category unit & sum
            'global_warming_potential_gwp_kg_co2e_-_a1-a3_per_category_unit' => 'a1a3_per_declared_unit',
            'global_warming_potential_gwp_kg_co2e_-_a1-a3_sum'               => 'a1a3_sum',

            // A1/A2/A3 basic
            'global_warming_potential_gwp_kg_co2e_-_a1' => 'gwp_a1',
            'global_warming_potential_gwp_kg_co2e_-_a2' => 'gwp_a2',
            'global_warming_potential_gwp_kg_co2e_-_a3' => 'gwp_a3',

            // A1/A2/A3 total
            'global_warming_potential_-_total_gwp-total_kg_co2e_-_a1' => 'gwp_total_a1',
            'global_warming_potential_-_total_gwp-total_kg_co2e_-_a2' => 'gwp_total_a2',
            'global_warming_potential_-_total_gwp-total_kg_co2e_-_a3' => 'gwp_total_a3',

            // LULUC
            'global_warming_potential_-_land_use_and_land_use_change_gwp-luluc_kg_co2e_-_a1'      => 'gwp_luluc_a1',
            'global_warming_potential_-_land_use_and_land_use_change_gwp-luluc_kg_co2e_-_a2'      => 'gwp_luluc_a2',
            'global_warming_potential_-_land_use_and_land_use_change_gwp-luluc_kg_co2e_-_a3'      => 'gwp_luluc_a3',
            'global_warming_potential_-_land_use_and_land_use_change_gwp-luluc_kg_co2e_-_a1-a3_sum' => 'gwp_luluc_a1a3_sum',

            // Biogenic
            'global_warming_potential_-_biogenic_gwp-biogenic_kg_co2e_-_a1'      => 'gwp_biogenic_a1',
            'global_warming_potential_-_biogenic_gwp-biogenic_kg_co2e_-_a2'      => 'gwp_biogenic_a2',
            'global_warming_potential_-_biogenic_gwp-biogenic_kg_co2e_-_a3'      => 'gwp_biogenic_a3',
            'global_warming_potential_-_biogenic_gwp-biogenic_kg_co2e_-_a1-a3_sum' => 'gwp_biogenic_a1a3_sum',

            // Fossil
            'global_warming_potential_-_fossil_fuels_gwp-fossil_kg_co2e_-_a1'      => 'gwp_fossil_a1',
            'global_warming_potential_-_fossil_fuels_gwp-fossil_kg_co2e_-_a2'      => 'gwp_fossil_a2',
            'global_warming_potential_-_fossil_fuels_gwp-fossil_kg_co2e_-_a3'      => 'gwp_fossil_a3',
            'global_warming_potential_-_fossil_fuels_gwp-fossil_kg_co2e_-_a1-a3_sum' => 'gwp_fossil_a1a3_sum',
        ];
    }

    private function buildPayload(array $r, array $idx, array $map): array
    {
        $p=[]; $extras=[];
        $num = fn($x)=>($x===null||$x==='')?null:(is_numeric(str_replace([',',' '],'',(string)$x))?(float)str_replace([',',' '],'',(string)$x):null);
        $int = fn($x)=>($x===null||$x==='')?null:(int)$num($x);
        $date= fn($x)=>($x?optional(\Carbon\Carbon::parse($x))->toDateString():null);

        foreach ($map as $from=>$to) {
            if (!array_key_exists($from,$idx)) continue;
            $raw = $r[$idx[$from]] ?? null;
            if ($raw===null || $raw==='') continue;

            // choose coercion based on target
            if (str_contains($to,'_date'))          $val=$date($raw);
            elseif (in_array($to, ['valid_to']))     $val=$date($raw);
            elseif (in_array($to, ['reference_year'])) $val=$int($raw);
            elseif (is_string($raw) && preg_match('/^\s*[\d,.\s-]+\s*$/', (string)$raw)) $val=$num($raw);
            else                                     $val=is_string($raw)?trim($raw):$raw;

            $p[$to]=$val;
        }

        // stash remaining columns into extras
        foreach ($idx as $col=>$i) {
            if (!array_key_exists($col,$map)) {
                $val=$r[$i]??null;
                if ($val!==null && $val!=='') $extras[$col]=$val;
            }
        }

        return [$p,$extras];
    }

    protected function resetProducts(): void
    {
        // IMPORTANT: truncate children first, then parents
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // add any other child tables that reference products here
        DB::table('product_epd_metrics')->truncate();

        // now it's safe to truncate products
        DB::table('products')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('Reset: truncated products and product_epd_metrics');
    }
}

