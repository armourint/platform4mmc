<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environmental_layers', function (Blueprint $table) {
            $table->id();

            // Dataset + system identity (mirrors your DatasetVersion + EnvironmentalFactor keys)
            $table->foreignId('dataset_version_id')->constrained('dataset_versions')->cascadeOnDelete();
            $table->string('system_code')->index();     // e.g., LGS, ICF, TF, BLOCK (derived from “MMC Method”)
            $table->string('assembly_id')->nullable();  // e.g., WALL_001 (from “System ID”)
            $table->string('system_name')->nullable();  // e.g., "2D LGS Exterior Wall Panel"
            $table->string('system_category')->nullable(); // e.g., "Wall"
            $table->string('source_header')->nullable();   // e.g., "A1-A3 Walls"

            // Layer metadata
            $table->unsignedInteger('layer_no')->nullable();
            $table->string('functional_role')->nullable();     // e.g., "Structural framing – stud"
            $table->string('generic_material')->nullable();    // e.g., "Wall Stud"

            // Dimensions / quantities (nullable for sparse rows)
            $table->decimal('length_m', 12, 4)->nullable();
            $table->decimal('height_m', 12, 4)->nullable();
            $table->decimal('thickness_m', 12, 6)->nullable();
            $table->decimal('element_volume_m3', 16, 6)->nullable();
            $table->unsignedInteger('element_number')->nullable();
            $table->decimal('total_volume_m3', 16, 6)->nullable();

            $table->decimal('density_kg_m3', 12, 2)->nullable();
            $table->decimal('mass_kg_m2', 12, 4)->nullable();

            // Emissions factors / results
            $table->decimal('carbon_factor', 12, 6)->nullable();     // typically kgCO2e/kg
            $table->decimal('a1a3_per_5_76_m2', 12, 4)->nullable();  // if provided
            $table->decimal('a1a3_per_m2', 12, 6)->nullable();       // explicit or computed

            // De-dup protection for re-imports
            $table->unique([
                'dataset_version_id','system_code','assembly_id','layer_no'
            ], 'env_layers_unique_row');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_layers');
    }
};
