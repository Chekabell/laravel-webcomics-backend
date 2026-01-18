<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Comic;
use App\Repositories\ComicTagRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TagService
{
    public function __construct(
        private ComicTagRepository $comicTagRepository
    ) {}

    /**
     * Синхронизировать теги комикса
     */
    public function syncComicTags(Comic $comic, ?array $tagIds): array
    {
        if (is_null($tagIds)) {
            $detachedCount = $this->comicTagRepository->detachAllTags($comic);
            return [
                'success' => true,
                'detached' => $detachedCount,
                'attached' => 0
            ];
        }

        $validatedTagIds = $this->validateAndNormalizeTagIds($tagIds);
        $result = $this->comicTagRepository->syncTags($comic, $validatedTagIds);

        return [
            'success' => true,
            'attached' => count($result['attached']),
            'detached' => count($result['detached']),
            'total' => $result['total']
        ];
    }

    /**
     * Валидация и нормализация ID тегов
     */
    protected function validateAndNormalizeTagIds(array $tagIds): array
    {
        return array_values(
            array_unique(
                array_filter(
                    array_map('intval', $tagIds),
                    fn($id) => $id > 0
                )
            )
        );
    }

}
