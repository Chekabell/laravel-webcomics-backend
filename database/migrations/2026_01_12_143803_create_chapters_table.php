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
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->integer('chapter_number')->index(); // Глава 1, 2, 3...
            $table->float('chapter_decimal')->nullable()->index(); // 1.5, 2.5 (для .5 глав)
            $table->integer('pages_count')->default(0);

            $table->date('published_at')->nullable()->index();

            $table->timestamps();

            // Индексы
            $table->unique(['comic_id', 'chapter_number', 'chapter_decimal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
