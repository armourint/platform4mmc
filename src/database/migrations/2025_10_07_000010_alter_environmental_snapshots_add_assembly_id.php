<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('environmental_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('environmental_snapshots','assembly_id')) {
                $table->string('assembly_id', 64)->nullable()->after('system_code');
            }
            if (!Schema::hasColumn('environmental_snapshots','system_category')) {
                $table->string('system_category', 32)->nullable()->after('assembly_id');
            }
            // replace unique(dataset_version_id, system_code) with (dv, code, assembly_id)
            $table->dropUnique('env_snap_dv_code_unique');
            $table->unique(['dataset_version_id','system_code','assembly_id'], 'env_snap_dv_code_assembly_unique');
        });
    }

    public function down(): void {
        Schema::table('environmental_snapshots', function (Blueprint $table) {
            if (Schema::hasColumn('environmental_snapshots','system_category')) {
                $table->dropColumn('system_category');
            }
            if (Schema::hasColumn('environmental_snapshots','assembly_id')) {
                $table->dropColumn('assembly_id');
            }
            $table->dropUnique('env_snap_dv_code_assembly_unique');
            $table->unique(['dataset_version_id','system_code'], 'env_snap_dv_code_unique');
        });
    }
};
