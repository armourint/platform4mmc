<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $cols = [
        // identifiers & taxonomy
        'uuid'                           => ['type' => 'uuid',     'index' => true],
        'country_code'                   => ['type' => 'string',   'len' => 4,  'index' => true],
        'country'                        => ['type' => 'string',   'index' => true],
        'category'                       => ['type' => 'string',   'index' => true],
        'subcategory'                    => ['type' => 'string'],
        'manufacturer'                   => ['type' => 'string',   'index' => true], // Product Owner
        'product_name'                   => ['type' => 'string',   'index' => true],

        // EPD programme metadata
        'epd_program'                    => ['type' => 'string'],
        'epd_number'                     => ['type' => 'string',   'index' => true], // Registration Number
        'reference_year'                 => ['type' => 'smallInteger', 'unsigned' => true],
        'valid_to'                       => ['type' => 'date'],

        // free-text technical/context fields
        'technology_description'         => ['type' => 'text'],
        'technical_purpose'              => ['type' => 'text'],
        'general_comment'                => ['type' => 'text'],
        'use_advice'                     => ['type' => 'text'],
        'lca_methodology_report'         => ['type' => 'text'],
        'data_quality_management'        => ['type' => 'text'],
        'review_type'                    => ['type' => 'string'],

        // physical properties
        'mass_per_du_kg'                 => ['type' => 'decimal', 'prec' => 12, 'scale' => 4],
        'weight_per_m2_kg'               => ['type' => 'decimal', 'prec' => 12, 'scale' => 4],
        'thickness_m'                    => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'length_m'                       => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'width_m'                        => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'height_m'                       => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'area_m2'                        => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'volume_m3'                      => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'specific_surface_m2_per_kg'     => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'density_kg_m3'                  => ['type' => 'decimal', 'prec' => 12, 'scale' => 4],
        'thermal_conductivity_w_mk'      => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'thermal_resistance_m2k_w'       => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'density_kg_litre'               => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'coverage_m2_per_litre'          => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],

        // declared/reference amounts
        'declared_amount'                => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'resulting_amount'               => ['type' => 'decimal', 'prec' => 12, 'scale' => 6],
        'reference_property'             => ['type' => 'string'],
        'reference_unit'                 => ['type' => 'string'],
        'category_unit'                  => ['type' => 'string'],

        // GWP A1–A3 (CEN/TR) per declared unit & splits
        'gwp_a1a3_per_unit'              => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_a1a3_sum'                   => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_a1'                         => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_a2'                         => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_a3'                         => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],

        // EN 15804+A2 split indicators
        'gwp_total_a1'                   => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_total_a2'                   => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_total_a3'                   => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],

        'gwp_luluc_a1'                   => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_luluc_a2'                   => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_luluc_a3'                   => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_luluc_a1a3_sum'             => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],

        'gwp_biogenic_a1'                => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_biogenic_a2'                => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_biogenic_a3'                => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_biogenic_a1a3_sum'          => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],

        'gwp_fossil_a1'                  => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_fossil_a2'                  => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_fossil_a3'                  => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],
        'gwp_fossil_a1a3_sum'            => ['type' => 'decimal', 'prec' => 14, 'scale' => 6],

        // catch-all
        'extras'                         => ['type' => 'json'],
    ];

    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            foreach ($this->cols as $name => $meta) {
                if (Schema::hasColumn('products', $name)) continue;

                switch ($meta['type']) {
                    case 'uuid':          $col = $t->uuid($name)->nullable(); break;
                    case 'string':        $col = isset($meta['len']) ? $t->string($name, $meta['len'])->nullable()
                                                                      : $t->string($name)->nullable(); break;
                    case 'text':          $col = $t->text($name)->nullable(); break;
                    case 'smallInteger':  $col = $t->smallInteger($name)->nullable(); if (($meta['unsigned'] ?? false)) $col->unsigned(); break;
                    case 'date':          $col = $t->date($name)->nullable(); break;
                    case 'decimal':       $col = $t->decimal($name, $meta['prec'], $meta['scale'])->nullable(); break;
                    case 'json':          $col = $t->json($name)->nullable(); break;
                    default:              $col = $t->string($name)->nullable();
                }

                if (($meta['index'] ?? false) === true) $t->index($name);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            // drop only if they exist to avoid rollback errors
            foreach (array_keys($this->cols) as $name) {
                if (Schema::hasColumn('products', $name)) {
                    // drop index first if present (Laravel will ignore if none)
                    try { $t->dropIndex([$name]); } catch (\Throwable $e) {}
                    try { $t->dropColumn($name); } catch (\Throwable $e) {}
                }
            }
        });
    }
};
