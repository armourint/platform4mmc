<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft');  // 'draft' or 'published'
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            // $table->softDeletes(); // optional: enable soft deletes
        });
    }
    public function down(): void {
        Schema::dropIfExists('articles');
    }
};
