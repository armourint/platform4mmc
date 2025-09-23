<?php

// database/migrations/2025_09_22_000001_add_optional_cols_to_epd_products.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('epd_products', function (Blueprint $table) {
            if (!Schema::hasColumn('epd_products', 'epd_url')) {
                $table->string('epd_url')->nullable()->after('epd_number');
            }
            if (!Schema::hasColumn('epd_products', 'product_code')) {
                $table->string('product_code')->nullable()->after('brand');
            }
        });
    }
    public function down(): void
    {
        Schema::table('epd_products', function (Blueprint $table) {
            if (Schema::hasColumn('epd_products', 'epd_url')) {
                $table->dropColumn('epd_url');
            }
            if (Schema::hasColumn('epd_products', 'product_code')) {
                $table->dropColumn('product_code');
            }
        });
    }
};
