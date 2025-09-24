<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dataset_versions', function (Blueprint $t) {
            if (!Schema::hasColumn('dataset_versions', 'is_current')) {
                $t->boolean('is_current')->default(false)->after('status');
            }
            if (!Schema::hasColumn('dataset_versions', 'published_at')) {
                $t->timestamp('published_at')->nullable()->after('is_current');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dataset_versions', function (Blueprint $t) {
            if (Schema::hasColumn('dataset_versions', 'published_at')) {
                $t->dropColumn('published_at');
            }
            if (Schema::hasColumn('dataset_versions', 'is_current')) {
                $t->dropColumn('is_current');
            }
        });
    }
};
