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
        Schema::create('contents', function (Blueprint $t) {
            $t->id();
            $t->string('type'); // article, case_study, resource
            $t->string('title');
            $t->text('excerpt')->nullable();
            $t->longText('body')->nullable();
            $t->json('meta')->nullable(); // links, attachments
            $t->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
