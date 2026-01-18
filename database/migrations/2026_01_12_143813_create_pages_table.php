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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->integer('page_number')->index(); // 1, 2, 3...
            $table->string('image'); // storage/comics/{id}/chapters/{id}/page_1.jpg
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('format', 10)->default('jpg'); // jpg, png, webp
            $table->bigInteger('file_size')->nullable(); // В байтах

            // Индексы
            $table->unique(['chapter_id', 'page_number']);
            $table->index(['chapter_id', 'page_number', 'format']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
