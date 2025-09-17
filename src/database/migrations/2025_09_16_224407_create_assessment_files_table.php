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
        Schema::create('assessment_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $t->enum('kind',['boq','takeoff','other']);
            $t->string('path');
            $t->string('mime')->nullable();
            $t->bigInteger('size_bytes')->nullable();
            $t->string('checksum')->nullable();
            $t->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_files');
    }
};
