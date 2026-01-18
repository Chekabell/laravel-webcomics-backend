<?php

namespace App\Observers;

use App\Models\Comic;

class ComicObserver
{
    /**
     * После обновления комикса.
     */
    public function updated(Comic $comic): void
    {
        $this->clearComicCache($comic);
    }

    /**
     * При удалении комикса (мягкое удаление).
     */
    public function deleted(Comic $comic): void
    {
        // Очищаем кэш
        $this->clearComicCache($comic);
    }

    /**
     * При восстановлении комикса.
     */
    public function restored(Comic $comic): void
    {
        $this->clearComicCache($comic);
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

        // Очищаем кэш связанных данных
        \Illuminate\Support\Facades\Cache::forget("comic.{$comic->id}.chapters");
        \Illuminate\Support\Facades\Cache::forget("comic.{$comic->id}.comments");
    }
}
