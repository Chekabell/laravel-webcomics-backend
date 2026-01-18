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
        Schema::create('rates', function (Blueprint $table) {
             $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Пользователь, который оценил');

            $table->foreignId('comic_id')
                ->constrained('comics')
                ->cascadeOnDelete()
                ->comment('Оцениваемый комикс');

            $table->unsignedTinyInteger('rate')
                ->check('rate >= 1 AND rate <= 5')
                ->comment('Оценка от 1 до 5');

            // Уникальный индекс: один пользователь = одна оценка на комикс
            $table->unique(['user_id', 'comic_id']);

            // Дополнительные индексы для статистики
            $table->index(['comic_id', 'rate']);

            // Комментарий о том, как будет обновляться статистика
            $table->comment('Оценки комиксов. При изменении - обновлять cached_rating в comics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
