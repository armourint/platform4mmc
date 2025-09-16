<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('city_id')->constrained()->cascadeOnDelete();
            $t->string('code');                // e.g. “R”
            $t->unsignedInteger('i')->nullable();
            $t->unsignedInteger('j')->nullable();
            $t->decimal('lat',10,6)->nullable();
            $t->decimal('lng',10,6)->nullable();
            $t->unique(['city_id','code']);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('locations'); }
};
