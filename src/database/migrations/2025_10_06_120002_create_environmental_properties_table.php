<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('environmental_properties')) {
            Schema::create('environmental_properties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('environmental_system_id')->index();
                $table->decimal('u_value_w_m2k', 8, 3)->nullable();
                $table->decimal('r_value_m2k_w', 8, 3)->nullable();
                $table->decimal('lambda_w_mk', 8, 4)->nullable();
                $table->string('fire_class', 32)->nullable();
                $table->unsignedInteger('acoustic_db')->nullable();
                $table->unsignedInteger('life_expectancy_years')->nullable();
                $table->json('notes_json')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_properties');
    }
};
