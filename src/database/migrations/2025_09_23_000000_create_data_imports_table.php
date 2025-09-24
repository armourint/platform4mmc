<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $t) {
            $t->id();
            $t->string('module', 50); // environmental | viability | manufacturers | products | counties
            $t->foreignId('dataset_version_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('original_name');
            $t->string('disk', 50)->default('public');
            $t->string('path');
            $t->string('status', 20)->default('queued'); // queued | processing | completed | failed
            $t->unsignedInteger('rows_processed')->default(0);
            $t->text('error')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
