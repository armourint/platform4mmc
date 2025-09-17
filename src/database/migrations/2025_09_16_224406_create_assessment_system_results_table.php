<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessment_system_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('system_id')->constrained('systems')->cascadeOnDelete();
            $t->boolean('is_viable');
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->index(['assessment_id','system_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_system_results');
    }
};
