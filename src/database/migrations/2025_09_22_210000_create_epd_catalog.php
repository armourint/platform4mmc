<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epd_products', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identity / catalog
            $table->uuid('uuid')->nullable()->index();
            $table->string('country_code', 4)->nullable()->index();
            $table->string('country', 128)->nullable();
            $table->string('category', 128)->nullable()->index();        // Product Category
            $table->string('subcategory', 128)->nullable()->index();     // Product Subcategory
            $table->string('owner', 191)->nullable()->index();           // Product Owner
            $table->string('name', 191)->nullable()->index();            // Product Name

            // Registration
            $table->string('reg_auth', 191)->nullable()->index();        // EPD Registration Authority
            $table->string('reg_number', 191)->nullable()->index();      // Registration Number
            $table->integer('reference_year')->nullable()->index();      // Reference Year
            $table->date('valid_until')->nullable()->index();            // Valid Until

            // Descriptors
            $table->text('technology_description')->nullable();
            $table->text('technical_purpose')->nullable();
            $table->text('general_comment')->nullable();
            $table->text('use_advice')->nullable();
            $table->text('lca_methodology_report')->nullable();
            $table->text('data_quality_management')->nullable();
            $table->string('type_of_review', 191)->nullable();

            // Dimensions / properties
            $table->decimal('mass_per_du_kg',           14, 6)->nullable(); // Mass per DU [kg]
            $table->decimal('weight_per_m2_kg',         14, 6)->nullable(); // Weight per m2 [kg]
            $table->decimal('thickness_m',              14, 6)->nullable();
            $table->decimal('length_m',                 14, 6)->nullable();
            $table->decimal('width_m',                  14, 6)->nullable();
            $table->decimal('height_m',                 14, 6)->nullable();
            $table->decimal('area_m2',                  14, 6)->nullable();
            $table->decimal('volume_m3',                14, 6)->nullable();
            $table->decimal('specific_surface_m2_per_kg',14, 6)->nullable();
            $table->decimal('density_kg_m3',            14, 6)->nullable();
            $table->decimal('thermal_conductivity_w_mk',14, 6)->nullable();
            $table->decimal('thermal_resistance_m2k_w', 14, 6)->nullable();
            $table->decimal('density_kg_litre',         14, 6)->nullable();
            $table->decimal('coverage_m2_per_litre',     14, 6)->nullable();

            // Declared amounts / units
            $table->decimal('declared_amount',  16, 6)->nullable();
            $table->decimal('resulting_amount', 16, 6)->nullable();
            $table->string('reference_property', 191)->nullable();
            $table->string('reference_unit',     64)->nullable();
            $table->string('category_unit',      64)->nullable()->index();

            // GWP summaries (A modules + splits)
            $table->decimal('gwp_a1a3_catunit', 16, 6)->nullable(); // per Category Unit
            $table->decimal('gwp_a1a3_sum',     16, 6)->nullable();

            $table->decimal('gwp_a1', 16, 6)->nullable();
            $table->decimal('gwp_a2', 16, 6)->nullable();
            $table->decimal('gwp_a3', 16, 6)->nullable();

            $table->decimal('gwp_total_a1', 16, 6)->nullable();
            $table->decimal('gwp_total_a2', 16, 6)->nullable();
            $table->decimal('gwp_total_a3', 16, 6)->nullable();

            $table->decimal('gwp_luluc_a1', 16, 6)->nullable();
            $table->decimal('gwp_luluc_a2', 16, 6)->nullable();
            $table->decimal('gwp_luluc_a3', 16, 6)->nullable();
            $table->decimal('gwp_luluc_a1a3_sum', 16, 6)->nullable();

            $table->decimal('gwp_biogenic_a1', 16, 6)->nullable();
            $table->decimal('gwp_biogenic_a2', 16, 6)->nullable();
            $table->decimal('gwp_biogenic_a3', 16, 6)->nullable();
            $table->decimal('gwp_biogenic_a1a3_sum', 16, 6)->nullable();

            $table->decimal('gwp_fossil_a1', 16, 6)->nullable();
            $table->decimal('gwp_fossil_a2', 16, 6)->nullable();
            $table->decimal('gwp_fossil_a3', 16, 6)->nullable();
            $table->decimal('gwp_fossil_a1a3_sum', 16, 6)->nullable();

            // helpful indexes
            $table->index(['category','subcategory','owner','name'], 'epd_products_catalog_idx');
            $table->index(['country_code','reg_auth','reg_number'], 'epd_products_reg_idx');

            $table->timestamps();
        });

        Schema::create('product_epd_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');            // FK to epd_products.id
            $table->string('module', 32)->index();               // e.g., A1, A2, A3, A1-A3, A4...
            $table->string('indicator', 128)->index();           // e.g., GWP-total, GWP-biogenic, ADP, AP, etc.
            $table->string('unit', 64)->nullable();
            $table->decimal('value', 18, 8)->nullable();

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')->on('epd_products')
                ->onDelete('cascade');

            $table->unique(['product_id','module','indicator','unit'], 'product_metric_uni');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_epd_metrics');
        Schema::dropIfExists('epd_products');
    }
};
