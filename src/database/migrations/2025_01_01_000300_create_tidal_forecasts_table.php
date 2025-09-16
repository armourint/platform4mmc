<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
  public function up(): void {
    Schema::create('tidal_forecasts', function (Blueprint $t) {
      $t->id();
      $t->timestamp('at')->index();
      $t->decimal('height_m',5,2);
      $t->string('location')->nullable()->index();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('tidal_forecasts'); }
};
