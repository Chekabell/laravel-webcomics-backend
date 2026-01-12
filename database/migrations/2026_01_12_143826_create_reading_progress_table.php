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
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->integer('current_page')->default(1);
            $table->float('read_percentage')->default(0); // 0.75 = 75%
            $table->boolean('is_completed')->default(false);
            $table->timestamp('last_read_at')->useCurrent();

            $table->timestamps();

            // Уникальный индекс: один прогресс на комикс для пользователя
            $table->unique(['user_id', 'comic_id']);

            // Индексы для быстрых запросов
            $table->index(['user_id', 'last_read_at']);
            $table->index(['comic_id', 'user_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
