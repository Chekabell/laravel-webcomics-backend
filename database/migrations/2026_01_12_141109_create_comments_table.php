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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Автор комментария');

            $table->foreignId('comic_id')
                ->constrained('comics')
                ->cascadeOnDelete()
                ->comment('Комикс, к которому комментарий');

            $table->text('text')
                ->comment('Текст комментария');

            // Для ответов на комментарии
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->nullOnDelete()
                ->comment('Родительский комментарий');

            $table->softDeletes(); // Для скрытия, а не удаления

            $table->timestamps();

            // Индексы для быстрого получения комментариев комикса
            $table->index(['comic_id', 'created_at']);
            $table->index(['comic_id', 'parent_id', 'created_at']);
            $table->index(['user_id', 'created_at']);

            // Для дерева комментариев
            $table->index(['parent_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
