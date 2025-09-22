<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('environmental_layers', function (Blueprint $table) {
            // MMC Method from sheet (e.g., LGS, TF, ICF, BLOCK). We still keep system_code as the normalized short code.
            $table->string('mmc_method')->nullable()->after('system_code');

            // Some earlier imports missed system_category on non-wall sheets — keep nullable but index it.
            $table->index('system_category');
        });
    }

    public function down(): void
    {
        Schema::table('environmental_layers', function (Blueprint $table) {
            $table->dropIndex(['system_category']);
            $table->dropColumn('mmc_method');
        });
    }
};
