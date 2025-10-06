<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('environmental_factors', function (Blueprint $table) {
            if (!Schema::hasColumn('environmental_factors','a5_per_m2')) {
                $table->decimal('a5_per_m2', 12, 6)->nullable()->after('a4_per_m2');
            }
            if (!Schema::hasColumn('environmental_factors','c1_c4_per_m2')) {
                $table->decimal('c1_c4_per_m2', 12, 6)->nullable()->after('a5_per_m2');
            }
            if (!Schema::hasColumn('environmental_factors','source')) {
                $table->string('source')->nullable()->after('c1_c4_per_m2');
            }
            // keep existing 'meta' column
        });
    }

    public function down(): void
    {
        Schema::table('environmental_factors', function (Blueprint $table) {
            if (Schema::hasColumn('environmental_factors','a5_per_m2')) {
                $table->dropColumn('a5_per_m2');
            }
            if (Schema::hasColumn('environmental_factors','c1_c4_per_m2')) {
                $table->dropColumn('c1_c4_per_m2');
            }
            if (Schema::hasColumn('environmental_factors','source')) {
                $table->dropColumn('source');
            }
        });
    }
};
