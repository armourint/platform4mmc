<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('predictions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('location_id')->constrained()->cascadeOnDelete();
            $t->dateTime('at')->index();
            $t->unsignedSmallInteger('year')->index();
            $t->decimal('target',8,4)->nullable();
            $t->decimal('rf',8,4)->nullable();
            $t->decimal('rbf',8,4)->nullable();
            $t->decimal('dt',8,4)->nullable();
            $t->decimal('ann',8,4)->nullable();
            $t->decimal('rnn',8,4)->nullable();
            $t->decimal('lstm',8,4)->nullable();
            $t->decimal('gru',8,4)->nullable();
            $t->index(['location_id','year','at']);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('predictions'); }
};
