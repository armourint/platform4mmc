<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
  public function up(): void {
    Schema::create('model_runs', function (Blueprint $t) {
      $t->id();
      $t->string('name')->index();
      $t->string('source_file');
      $t->json('meta')->nullable();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('model_runs'); }
};
