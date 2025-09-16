<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
  public function up(): void {
    Schema::create('model_observations', function (Blueprint $t) {
      $t->id();
      $t->foreignId('model_run_id')->constrained()->cascadeOnDelete();
      $t->timestamp('observed_at')->nullable()->index();
      $t->string('location')->nullable()->index();
      $t->json('inputs');
      $t->json('outputs')->nullable();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('model_observations'); }
};
