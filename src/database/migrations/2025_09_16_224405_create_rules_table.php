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
        Schema::create('rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dataset_version_id')->constrained()->cascadeOnDelete();
            $t->string('module'); // 'viability'
            $t->foreignId('system_id')->constrained('systems')->cascadeOnDelete();
            $t->enum('rule_type',['include','exclude'])->default('exclude');
            $t->json('conditions_json');        // {"residential_type":{"in":["low","medium"]}}
            $t->text('reason')->nullable();     // reason for exclusion
            $t->integer('priority')->default(0);
            $t->timestamps();
            $t->index(['dataset_version_id','module','system_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
