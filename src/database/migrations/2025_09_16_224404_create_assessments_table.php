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
        Schema::create('assessments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->enum('type',['viability','environmental']);
            $t->enum('status',['draft','completed'])->default('draft');
            $t->foreignId('dataset_version_id')->constrained()->restrictOnDelete();
            $t->json('inputs')->nullable();
            $t->json('score')->nullable();     // {"viability_score":3,...} or env KPIs
            $t->json('summary')->nullable();
            $t->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
