<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epd_products', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Optional grouping to your dataset version (environmental)
            $table->unsignedBigInteger('dataset_version_id')->nullable();
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('set null');

            // Who/what
            $table->string('product_name');
            $table->string('manufacturer')->nullable();
            $table->string('brand')->nullable();
            $table->string('mmc_method')->nullable();          // e.g. LGS / TF / ICF
            $table->string('product_category')->nullable();    // Wall / Cladding / Slab / etc.
            $table->string('product_subcategory')->nullable();

            // EPD meta
            $table->string('epd_programme')->nullable();       // e.g. EPD Ireland / IBU / BRE
            $table->string('epd_number')->nullable();          // programme EPD identifier
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('country')->nullable();

            // Declared unit and basic phys. data
            $table->string('declared_unit')->nullable();       // e.g. kg, m2, m3, piece
            $table->decimal('density_kg_m3', 12, 3)->nullable();
            $table->decimal('thickness_m', 8, 4)->nullable();
            $table->decimal('mass_per_m2', 12, 4)->nullable();

            // MVP indicators (GWP) — store both common normalisations if present
            $table->decimal('gwp_a1a3_per_unit', 16, 6)->nullable();
            $table->decimal('gwp_a4_per_unit', 16, 6)->nullable();
            $table->decimal('gwp_a1a3_per_m2', 16, 6)->nullable();
            $table->decimal('gwp_a4_per_m2', 16, 6)->nullable();

            // Transport info (optional)
            $table->string('a4_transport_mode')->nullable();   // e.g. truck, ship
            $table->decimal('a4_transport_distance_km', 12, 2)->nullable();

            // Free-form stores for everything else we don’t have explicit columns for
            $table->json('modules')->nullable();               // e.g. {"A1-A3": {...}, "A4": {...}, "B": {...}, ...}
            $table->json('indicators')->nullable();            // e.g. {"GWP-total":..., "AP":..., "EP":..., ...}
            $table->json('properties')->nullable();            // raw extra columns from the sheet

            $table->string('source')->nullable();              // filename
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Uniqueness heuristics: prefer official EPD identifiers if present
            $table->unique(['epd_programme','epd_number'], 'epd_products_programme_number_unique');
            $table->index(['product_category', 'mmc_method']);
            $table->index(['manufacturer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epd_products');
    }
};
