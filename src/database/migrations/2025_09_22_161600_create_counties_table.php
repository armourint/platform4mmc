<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('counties', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Geo names
            $table->string('code')->unique();     // e.g. "IEDL"
            $table->string('name');               // e.g. "Donegal"
            $table->string('source')->nullable(); // e.g. "https://simplemaps.com"
            // Geometry stored as GeoJSON
            $table->json('geometry')->nullable();
            // Optional quick lookup fields
            $table->decimal('centroid_lat', 10, 6)->nullable();
            $table->decimal('centroid_lng', 10, 6)->nullable();

            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counties');
    }
};
