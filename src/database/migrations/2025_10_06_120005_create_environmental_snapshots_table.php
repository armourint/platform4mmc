<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('environmental_snapshots')) {
            Schema::create('environmental_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dataset_version_id')->index();
                $table->string('system_code', 64)->index();
                $table->json('kpi_json')->nullable();
                $table->json('layers_json')->nullable();
                $table->json('hotspots_json')->nullable();
                $table->json('chart_rows_json')->nullable();
                $table->string('checksum', 64)->nullable();
                $table->timestamps();

                $table->unique(['dataset_version_id','system_code'], 'env_snap_dv_code_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_snapshots');
    }
};
