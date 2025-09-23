<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identity
            $table->string('name');                            // e.g. "Acme Panels"
            $table->string('mmc_method')->nullable();          // e.g. "LGS", "TF", "ICF"
            $table->string('product_category')->nullable();    // e.g. "Wall", "Cladding", "Slab"
            $table->string('product_subcategory')->nullable(); // e.g. "2D Panel", "Brick Slip Rail"

            // Contact/location
            $table->string('address')->nullable();
            $table->string('county_code')->nullable();         // FK-like (from counties.code) if we can map
            $table->string('county_name')->nullable();         // original text if code not found
            $table->string('country')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Geo (no MySQL GIS; just lat/lng)
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();

            // Misc
            $table->json('properties')->nullable();            // any extra columns from the sheet
            $table->string('source')->nullable();              // e.g. "mmc_manufacturer_map_list.xls"
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Useful indexes
            $table->index(['county_code']);
            $table->index(['lat', 'lng']);
            $table->index(['mmc_method', 'product_category']);

            // A conservative uniqueness to avoid obvious dupes while allowing multiple categories
            $table->unique(['name', 'county_name', 'product_category'], 'manufacturers_name_county_cat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};