<?php

namespace App\Repositories;

use App\DTOs\IndexComicDTO;
use App\DTOs\StoreComicDTO;
use App\DTOs\UpdateComicDTO;
use App\Models\Comic;
use Illuminate\Support\Facades\DB;

class ComicRepository{

    public function indexByFilter(IndexComicDTO $indexComicDTO, bool $isWriterOrAdmin = false){
        $query = Comic::withCount(['chapters', 'rates', 'comments'])
                ->orderBy('created_at', 'desc');

        // Фильтрация по типу
        if ($indexComicDTO->type) {
            $query->where('type', '=',$indexComicDTO->type);
        }

        // Фильтрация по году
        if ($indexComicDTO->yearMax) {
            $query->where('year', '<=',$indexComicDTO->yearMax);
        }

        if ($indexComicDTO->yearMin) {
            $query->where('year', '>=',$indexComicDTO->yearMin);
        }

        // Фильтрация по статусу
        if ($indexComicDTO->status && $isWriterOrAdmin) {
            $query->where('status', $indexComicDTO->status);
        } else {
            $query->published();
        }

        // Фильтрация по тегам
        if ($indexComicDTO->tags) {
            // Для каждого тега добавляем отдельный whereHas
            foreach ($indexComicDTO->tags as $tagId) {
                $query->whereHas('tags', function ($q) use ($tagId) {
                    $q->where('tags.id', $tagId);
                });
            }
        }

        // Поиск
        if ($indexComicDTO->search) {
            $query->search($indexComicDTO->search);
        }

        switch ($indexComicDTO->sort) {
            case 'popular':
                $query->popular();
                break;
            case 'views_desc':
                $query->orderBy('cached_views_count', 'desc');
                break;
            case 'views_asc':
                $query->orderBy('cached_views_count', 'asc');
                break;
            case 'ratings_desc':
                $query->orderBy('cached_ratings_count', 'desc');
                break;
            case 'ratings_asc':
                $query->orderBy('cached_ratings_count', 'asc');
                break;
        }

        return $query;
    }

    public function store(StoreComicDTO $storeComicDTO){
        return Comic::create($storeComicDTO->toArray());
    }

    public function show(Comic $comic){
        $comic->load([
            'author',
            'tags',
            'chapters'
        ]);
        return $comic;
    }

    public function update(UpdateComicDTO $updateComicDTO, Comic $comic){
        $comic->title = $updateComicDTO->title ?? $comic->title;
        $comic->description = $updateComicDTO->description ?? $comic->description;
        $comic->year = $updateComicDTO->year ?? $comic->year;
        $comic->type = $updateComicDTO->type ?? $comic->type;
        $comic->cover_image = $updateComicDTO->path_cover_image ?? $comic->cover_image;
        $comic->status = $updateComicDTO->status ?? $comic->status;
        return $comic->update();
    }

    public function destroy(Comic $comic){
        $comic->delete();
    }
}
