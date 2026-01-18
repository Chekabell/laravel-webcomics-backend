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
        Schema::create('comic_tag', function (Blueprint $table) {
            $table->foreignId('comic_id')
            ->constrained('comics')
            ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->restrictOnDelete();

            // Составной первичный ключ
            $table->primary(['comic_id', 'tag_id']);

            // Индексы для быстрого поиска в обе стороны
            $table->index(['tag_id', 'comic_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comic_tag');
    }
};
