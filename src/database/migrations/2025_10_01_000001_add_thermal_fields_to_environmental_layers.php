<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('environmental_layers', function (Blueprint $table) {
            // Units & thermal properties
            if (!Schema::hasColumn('environmental_layers', 'carbon_factor_unit')) {
                $table->string('carbon_factor_unit', 32)->nullable()->after('carbon_factor');
            }
            if (!Schema::hasColumn('environmental_layers', 'thickness_m')) {
                $table->decimal('thickness_m', 12, 5)->nullable()->after('mass_kg_m2');
            }
            if (!Schema::hasColumn('environmental_layers', 'thermal_conductivity_w_mk')) {
                $table->decimal('thermal_conductivity_w_mk', 12, 6)->nullable()->after('thickness_m');
            }
            if (!Schema::hasColumn('environmental_layers', 'r_value_m2k_w')) {
                $table->decimal('r_value_m2k_w', 12, 6)->nullable()->after('thermal_conductivity_w_mk');
            }
            if (!Schema::hasColumn('environmental_layers', 'u_value_w_m2k')) {
                $table->decimal('u_value_w_m2k', 12, 6)->nullable()->after('r_value_m2k_w');
            }
            if (!Schema::hasColumn('environmental_layers', 'life_expectancy_years')) {
                $table->decimal('life_expectancy_years', 12, 2)->nullable()->after('u_value_w_m2k');
            }
        });
    }

    public function down(): void
    {
        Schema::table('environmental_layers', function (Blueprint $table) {
            foreach ([
                'carbon_factor_unit',
                'thickness_m',
                'thermal_conductivity_w_mk',
                'r_value_m2k_w',
                'u_value_w_m2k',
                'life_expectancy_years',
            ] as $col) {
                if (Schema::hasColumn('environmental_layers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
