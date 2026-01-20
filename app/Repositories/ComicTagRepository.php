<?php

namespace App\Repositories;

use App\Models\Comic;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComicTagRepository
{
    /**
     * Полная синхронизация тегов комикса с обновлением счетчиков
     */
    public function syncTags(Comic $comic, array $tagIds): array
    {
        return DB::transaction(function () use ($comic, $tagIds) {
            // Получаем текущие теги
            $currentTagIds = $comic->tags()->pluck('tags.id')->toArray();

            // Находим разницу
            $tagsToAttach = array_values(array_diff($tagIds, $currentTagIds));
            $tagsToDetach = array_values(array_diff($currentTagIds, $tagIds));

            // Выполняем синхронизацию
            $comic->tags()->sync($tagIds);

            // Обновляем счетчики
            $this->updateUsageCounters($tagsToAttach, $tagsToDetach);

            return [
                'attached' => $tagsToAttach,
                'detached' => $tagsToDetach,
                'total' => count($tagIds)
            ];
        });
    }


    /**
     * Удалить все теги комикса
     */
    public function detachAllTags(Comic $comic): int
    {
        return DB::transaction(function () use ($comic) {
            $tagIds = $comic->tags()->pluck('tags.id')->toArray();

            if (!empty($tagIds)) {
                $comic->tags()->detach();
                $this->decrementTagsUsage($tagIds);

                Log::info('All tags detached from comic', [
                    'comic_id' => $comic->id,
                    'tag_count' => count($tagIds)
                ]);
            }

            return count($tagIds);
        });
    }

    /**
     * Обновить счетчики использования тегов
     */
    protected function updateUsageCounters(array $tagsToAttach, array $tagsToDetach): void
    {
        if (!empty($tagsToAttach)) {
            $this->incrementTagsUsage($tagsToAttach);
        }

        if (!empty($tagsToDetach)) {
            $this->decrementTagsUsage($tagsToDetach);
        }
    }

    /**
     * Увеличить счетчики использования тегов
     */
    protected function incrementTagsUsage(array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        // Используем DB::raw для безопасности
        DB::table('tags')
            ->whereIn('id', $tagIds)
            ->update([
                'usage_count' => DB::raw('usage_count + 1'),
            ]);

        Log::debug('Tags usage incremented', ['tag_ids' => $tagIds]);
    }

    /**
     * Уменьшить счетчики использования тегов
     */
    protected function decrementTagsUsage(array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        // Используем CASE для защиты от отрицательных значений
        DB::table('tags')
            ->whereIn('id', $tagIds)
            ->where('usage_count', '>', 0)
            ->update([
                'usage_count' => DB::raw('CASE WHEN usage_count > 0 THEN usage_count - 1 ELSE 0 END'),
            ]);

        Log::debug('Tags usage decremented', ['tag_ids' => $tagIds]);
    }
}
