<?php

namespace App\Observers;

use App\Models\Comic;

class ComicObserver
{
    /**
     * При создании комикса.
     */
    public function creating(Comic $comic): void
    {
        // Автоматически устанавливаем published_at если статус published
        if ($comic->status === 'published' && !$comic->published_at) {
            $comic->published_at = now();
        }
    }

    /**
     * Перед обновлением комикса.
     */
    public function updating(Comic $comic): void
    {
        // Если статус меняется на published, устанавливаем дату
        if ($comic->isDirty('status') && $comic->status === 'published') {
            $comic->published_at = $comic->published_at ?? now();
        }

        // Если статус меняется с published на другой, можно очистить дату
        if ($comic->isDirty('status') && $comic->getOriginal('status') === 'published') {
            $comic->published_at = null;
        }
    }

    /**
     * После обновления комикса.
     */
    public function updated(Comic $comic): void
    {
        // Если изменились поля, влияющие на поиск - очищаем кэш
        if ($comic->wasChanged(['title', 'status', 'is_featured'])) {
            $this->clearComicCache($comic);
        }

        // Если комикс опубликован - отправляем уведомление
        if ($comic->wasChanged('status') && $comic->status === 'published') {
            // event(new ComicPublished($comic));
        }
    }

    /**
     * При удалении комикса (мягкое удаление).
     */
    public function deleted(Comic $comic): void
    {
        // Очищаем кэш
        $this->clearComicCache($comic);

        // Можно также удалить связанные файлы изображений
        // Storage::delete($comic->cover_image);
        // foreach ($comic->images ?? [] as $image) {
        //     Storage::delete($image);
        // }
    }

    /**
     * При восстановлении комикса.
     */
    public function restored(Comic $comic): void
    {
        $this->clearComicCache($comic);
    }

    /**
     * При полном удалении комикса.
     */
    public function forceDeleted(Comic $comic): void
    {
        // Удаляем связанные файлы
        $this->deleteComicFiles($comic);

        // Очищаем кэш
        $this->clearComicCache($comic);
    }

    // ============ КЛЮЧЕВЫЕ МЕТОДЫ ДЛЯ АВТОМАТИЧЕСКОГО ОБНОВЛЕНИЯ ============

    /**
     * Обновить кешированные значения рейтинга.
     * Вызывается при изменении оценок (Rate).
     */
    public function ratingChanged(Comic $comic): void
    {
        $stats = $comic->rates()
            ->selectRaw('COUNT(*) as count, AVG(rate) as avg')
            ->first();

        $comic->updateQuietly([
            'cached_ratings_count' => $stats->count ?? 0,
            'cached_rating' => round($stats->avg ?? 0, 2),
        ]);

        $this->clearComicCache($comic);
    }

    /**
     * Обновить счётчик комментариев.
     * Вызывается при добавлении/удалении комментариев.
     */
    public function commentChanged(Comic $comic): void
    {
        $comic->updateQuietly([
            'cached_comments_count' => $comic->comments()->count(),
        ]);

        $this->clearComicCache($comic);
    }

    /**
     * Обновить счётчики глав и страниц.
     * Вызывается при изменении глав (Chapter).
     */
    public function chapterChanged(Comic $comic): void
    {
        $publishedChapters = $comic->publishedChapters();

        $comic->updateQuietly([
            'cached_chapters_count' => $publishedChapters->count(),
            'cached_pages_count' => $publishedChapters->sum('pages_count'),
        ]);

        $this->clearComicCache($comic);
    }

    /**
     * Увеличить счётчик просмотров.
     * Вызывается при каждом просмотре комикса.
     */
    public function viewed(Comic $comic): void
    {
        $comic->increment('cached_views_count');
        // Не очищаем кэш здесь - только инкрементируем
    }

    // ============ ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ============

    /**
     * Очистить кэш комикса.
     */
    private function clearComicCache(Comic $comic): void
    {
        // Очищаем кэш самого комикса
        \Illuminate\Support\Facades\Cache::forget("comic.{$comic->id}");
        \Illuminate\Support\Facades\Cache::forget("comic.{$comic->id}.full");

        // Очищаем списки комиксов (если кэшируете)
        \Illuminate\Support\Facades\Cache::tags(['comics'])->flush();

        // Очищаем кэш связанных данных
        \Illuminate\Support\Facades\Cache::forget("comic.{$comic->id}.chapters");
        \Illuminate\Support\Facades\Cache::forget("comic.{$comic->id}.comments");
    }

    /**
     * Удалить файлы комикса.
     */
    private function deleteComicFiles(Comic $comic): void
    {
        $storage = \Illuminate\Support\Facades\Storage::disk('public');

        // Удаляем обложку
        if ($comic->cover_image && $storage->exists($comic->cover_image)) {
            $storage->delete($comic->cover_image);
        }

        // Удаляем дополнительные изображения
        foreach ($comic->images ?? [] as $image) {
            if ($storage->exists($image)) {
                $storage->delete($image);
            }
        }
    }
}
