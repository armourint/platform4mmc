<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('environmental_system_media')) {
            Schema::create('environmental_system_media', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('environmental_system_id')->index();
                $table->enum('type', ['thumbnail','detail','drawing'])->default('thumbnail');
                $table->string('path');
                $table->string('alt')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_system_media');
    }
};
