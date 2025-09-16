<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $t) {
            $t->unique(['location_id', 'at'], 'predictions_location_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $t) {
            $t->dropUnique('predictions_location_at_unique');
        });
    }
};
