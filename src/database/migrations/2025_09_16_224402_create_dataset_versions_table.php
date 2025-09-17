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
        Schema::create('dataset_versions', function (Blueprint $t) {
            $t->id();
            $t->string('module'); // 'viability','environmental'
            $t->string('version_label'); // 'v2025.09.16'
            $t->enum('status',['draft','published','archived'])->default('draft');
            $t->date('effective_from')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['module','status']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_versions');
    }
};
