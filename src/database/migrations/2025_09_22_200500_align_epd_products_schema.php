<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- EPD PRODUCTS ---------------------------------------------------
        Schema::table('epd_products', function (Blueprint $table) {
            // identity / catalog
            if (!Schema::hasColumn('epd_products','uuid')) $table->uuid('uuid')->nullable()->index();
            if (!Schema::hasColumn('epd_products','country_code')) $table->string('country_code', 4)->nullable()->index();
            if (!Schema::hasColumn('epd_products','country')) $table->string('country', 128)->nullable();
            if (!Schema::hasColumn('epd_products','category')) $table->string('category', 128)->nullable()->index();
            if (!Schema::hasColumn('epd_products','subcategory')) $table->string('subcategory', 128)->nullable()->index();
            if (!Schema::hasColumn('epd_products','owner')) $table->string('owner', 191)->nullable()->index();
            if (!Schema::hasColumn('epd_products','name')) $table->string('name', 191)->nullable()->index();
            if (!Schema::hasColumn('epd_products','reg_auth')) $table->string('reg_auth', 191)->nullable()->index();
            if (!Schema::hasColumn('epd_products','reg_number')) $table->string('reg_number', 191)->nullable()->index();
            if (!Schema::hasColumn('epd_products','reference_year')) $table->integer('reference_year')->nullable()->index();
            if (!Schema::hasColumn('epd_products','valid_until')) $table->date('valid_until')->nullable()->index();

            // descriptors
            if (!Schema::hasColumn('epd_products','technology_description')) $table->text('technology_description')->nullable();
            if (!Schema::hasColumn('epd_products','technical_purpose')) $table->text('technical_purpose')->nullable();
            if (!Schema::hasColumn('epd_products','general_comment')) $table->text('general_comment')->nullable();
            if (!Schema::hasColumn('epd_products','use_advice')) $table->text('use_advice')->nullable();
            if (!Schema::hasColumn('epd_products','lca_methodology_report')) $table->text('lca_methodology_report')->nullable();
            if (!Schema::hasColumn('epd_products','data_quality_management')) $table->text('data_quality_management')->nullable();
            if (!Schema::hasColumn('epd_products','type_of_review')) $table->string('type_of_review', 191)->nullable();

            // dimensions / derived (DECIMAL to preserve XLS precision)
            $dec12_4  = fn($c)=>$c->decimal(12,4)->nullable();
            $dec12_6  = fn($c)=>$c->decimal(12,6)->nullable();

            if (!Schema::hasColumn('epd_products','mass_per_du_kg')) $dec12_4($table->double('mass_per_du_kg', 12, 4));
            if (!Schema::hasColumn('epd_products','weight_per_m2_kg')) $dec12_4($table->double('weight_per_m2_kg', 12, 4));
            if (!Schema::hasColumn('epd_products','thickness_m')) $dec12_6($table->decimal('thickness_m', 12, 6));
            if (!Schema::hasColumn('epd_products','length_m')) $dec12_6($table->decimal('length_m', 12, 6));
            if (!Schema::hasColumn('epd_products','width_m'))  $dec12_6($table->decimal('width_m', 12, 6));
            if (!Schema::hasColumn('epd_products','height_m')) $dec12_6($table->decimal('height_m', 12, 6));
            if (!Schema::hasColumn('epd_products','area_m2'))  $dec12_6($table->decimal('area_m2', 12, 6));
            if (!Schema::hasColumn('epd_products','volume_m3')) $dec12_6($table->decimal('volume_m3', 12, 6));
            if (!Schema::hasColumn('epd_products','specific_surface_m2_per_kg')) $dec12_6($table->decimal('specific_surface_m2_per_kg', 12, 6));
            if (!Schema::hasColumn('epd_products','density_kg_m3')) $dec12_6($table->decimal('density_kg_m3', 12, 6));
            if (!Schema::hasColumn('epd_products','thermal_conductivity_w_mk')) $dec12_6($table->decimal('thermal_conductivity_w_mk', 12, 6));
            if (!Schema::hasColumn('epd_products','thermal_resistance_m2k_w')) $dec12_6($table->decimal('thermal_resistance_m2k_w', 12, 6));
            if (!Schema::hasColumn('epd_products','density_kg_litre')) $dec12_6($table->decimal('density_kg_litre', 12, 6));
            if (!Schema::hasColumn('epd_products','coverage_m2_per_litre')) $dec12_6($table->decimal('coverage_m2_per_litre', 12, 6));

            // declared amounts / units
            if (!Schema::hasColumn('epd_products','declared_amount')) $table->decimal('declared_amount', 14, 6)->nullable();
            if (!Schema::hasColumn('epd_products','resulting_amount')) $table->decimal('resulting_amount', 14, 6)->nullable();
            if (!Schema::hasColumn('epd_products','reference_property')) $table->string('reference_property', 191)->nullable();
            if (!Schema::hasColumn('epd_products','reference_unit')) $table->string('reference_unit', 64)->nullable();
            if (!Schema::hasColumn('epd_products','category_unit')) $table->string('category_unit', 64)->nullable()->index();

            // GWP summary (keep them on main table for quick filters)
            $gwp = function(string $col, Blueprint $t) {
                if (!Schema::hasColumn('epd_products',$col)) $t->decimal($col, 14, 6)->nullable();
            };
            $gwp('gwp_a1a3_catunit', $table);
            $gwp('gwp_a1a3_sum',     $table);

            $gwp('gwp_a1', $table); $gwp('gwp_a2', $table); $gwp('gwp_a3', $table);
            $gwp('gwp_total_a1', $table); $gwp('gwp_total_a2', $table); $gwp('gwp_total_a3', $table);

            $gwp('gwp_luluc_a1', $table); $gwp('gwp_luluc_a2', $table); $gwp('gwp_luluc_a3', $table);
            $gwp('gwp_luluc_a1a3_sum', $table);

            $gwp('gwp_biogenic_a1', $table); $gwp('gwp_biogenic_a2', $table); $gwp('gwp_biogenic_a3', $table);
            $gwp('gwp_biogenic_a1a3_sum', $table);

            $gwp('gwp_fossil_a1', $table); $gwp('gwp_fossil_a2', $table); $gwp('gwp_fossil_a3', $table);
            $gwp('gwp_fossil_a1a3_sum', $table);

            // helpful compound index for catalog searches
            if (!Schema::hasColumn('epd_products','__idx_stub')) {
                // hack to conditionally create composite indexes without duplicates across environments
                $table->string('__idx_stub', 1)->nullable(); // removed in down()
                $table->index(['category','subcategory','owner','name'], 'epd_products_catalog_idx');
                $table->index(['country_code','reg_auth','reg_number'], 'epd_products_reg_idx');
            }
        });
    }

    public function down(): void
    {
        // Keep columns; only clean helper & indexes so down() is safe.
        Schema::table('epd_products', function (Blueprint $table) {
            if (Schema::hasColumn('epd_products','__idx_stub')) {
                $table->dropIndex('epd_products_catalog_idx');
                $table->dropIndex('epd_products_reg_idx');
                $table->dropColumn('__idx_stub');
            }
        });
    }
};
