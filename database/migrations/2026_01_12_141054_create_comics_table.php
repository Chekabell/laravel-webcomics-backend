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
        Schema::create('comics', function (Blueprint $table) {
            // 1. ОСНОВНЫЕ ПОЛЯ
            $table->id();
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Автор комикса');

            // 2. КОНТЕНТ
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->year('year')
                ->default(date('Y'))
                ->index()
                ->comment('Год выпуска');

            $table->enum('type', ['manga', 'manhwa', 'manhua', 'western', 'other'])
                ->default('manga')
                ->index()
                ->comment('Тип комикса');

            // 3. МЕДИА
            $table->string('cover_image')
                ->comment('Обложка в storage');
            $table->json('images')->nullable()
                ->comment('Дополнительные изображения');
            $table->string('external_link')->nullable()
                ->comment('Ссылка на оригинал');

            // 4. КЕШИРОВАННАЯ СТАТИСТИКА (оптимизация)
            $table->decimal('cached_rating', 3, 2)
                ->default(0)
                ->index()
                ->comment('Средний рейтинг');

            $table->unsignedInteger('cached_ratings_count')
                ->default(0)
                ->index()
                ->comment('Количество оценок');

            $table->unsignedInteger('cached_views_count')
                ->default(0)
                ->index()
                ->comment('Просмотры');

            $table->unsignedInteger('cached_comments_count')
                ->default(0)
                ->index()
                ->comment('Комментарии');

            $table->unsignedInteger('cached_chapters_count')
                ->default(0)
                ->index()
                ->comment('Количество глав (опубликованных)');

            $table->unsignedInteger('cached_pages_count')
                ->default(0)
                ->index()
                ->comment('Общее количество страниц во всех главах');

            // 5. МЕТАДАННЫЕ
            $table->json('metadata')->nullable()
                ->comment('Доп. данные: жанры, возрастной рейтинг и т.д.');

            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft')
                ->index()
                ->comment('Статус публикации');

            $table->boolean('is_featured')->default(false)
                ->index()
                ->comment('Закреплён в ленте');

            $table->unsignedInteger('reading_time')->nullable()
                ->comment('Время чтения в минутах');

            // 6. ТАЙМСТАМПЫ
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('published_at')->nullable()
                ->index()
                ->comment('Дата публикации');

            // 7. ИНДЕКСЫ ДЛЯ ПРОИЗВОДИТЕЛЬНОСТИ
            $table->index(['status', 'published_at']);
            $table->index(['type', 'year', 'cached_rating']);
            $table->index(['author_id', 'created_at']);
            $table->index(['is_featured', 'cached_rating', 'created_at']);
            $table->index(['cached_chapters_count', 'cached_pages_count']);

            // 8. ПОЛНОТЕКСТОВЫЙ ПОИСК (только для PostgreSQL/MySQL)
            if (!app()->runningUnitTests() && config('database.default') !== 'sqlite') {
                $table->fullText(['title', 'description']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comics');
    }
};
