<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('rules', function (Blueprint $t) {
            $t->string('system_code')->nullable()->after('system_id');
            $t->index(['dataset_version_id','module','system_code']);
        });
    }
    public function down(): void {
        Schema::table('rules', function (Blueprint $t) {
            $t->dropIndex(['dataset_version_id','module','system_code']);
            $t->dropColumn('system_code');
        });
    }
};