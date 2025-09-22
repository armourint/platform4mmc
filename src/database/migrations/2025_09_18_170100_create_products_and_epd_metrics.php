<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('category')->nullable();          // e.g., insulation, concrete, timber
            $table->string('standard')->nullable();          // EN xxxx
            $table->string('declared_unit')->nullable();     // e.g., kg, m2, m3, unit
            $table->json('meta')->nullable();                // freeform
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_epd_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('country')->nullable()->index();  // IE, EU, etc
            $table->string('program')->nullable();           // EPD program
            $table->string('reference')->nullable();         // Doc or ID
            $table->string('declared_unit')->nullable();     // normalize if differs
            // Core MVP indicators (others can be added later)
            $table->decimal('a1_a3_per_unit', 14, 6)->nullable(); // kgCO2e per declared unit
            $table->decimal('a4_per_unit', 14, 6)->nullable();    // kgCO2e per declared unit
            // Useful derived/meta fields
            $table->decimal('density', 12, 6)->nullable();        // if present
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('product_epd_metrics');
        Schema::dropIfExists('products');
    }
};
