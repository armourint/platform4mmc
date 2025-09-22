<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('environmental_factors', function (Blueprint $table) {
            $table->id();
            $table->string('system_code'); // e.g., BLOCK, ICF, LGS, TIMBER
            $table->foreignId('dataset_version_id')->constrained('dataset_versions');
            $table->decimal('a1_a3_per_m2', 12, 6)->nullable(); // kgCO2e per m2
            $table->decimal('a4_per_m2', 12, 6)->nullable();     // kgCO2e per m2
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['dataset_version_id','system_code']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('environmental_factors');
    }
};
