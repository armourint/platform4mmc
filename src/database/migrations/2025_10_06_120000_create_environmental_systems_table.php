<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('environmental_systems')) {
            Schema::create('environmental_systems', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dataset_version_id')->index();
                $table->string('system_code', 64)->index();
                $table->string('assembly_id')->nullable();
                $table->string('system_name')->nullable();
                $table->string('system_category', 64)->nullable(); // Wall|Cladding|Slab
                $table->string('mmc_method', 64)->nullable();      // BLOCK|ICF|LGS|TIMBER…
                $table->boolean('is_active')->default(true);
                $table->string('slug')->nullable()->unique();
                $table->timestamps();

                $table->unique(['dataset_version_id','system_code'], 'env_sys_dv_code_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_systems');
    }
};
