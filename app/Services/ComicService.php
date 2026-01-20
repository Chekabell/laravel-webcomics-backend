<?php

namespace App\Services;

use Exception;
use App\DTOs\IndexComicDTO;
use App\DTOs\StoreComicDTO;
use App\DTOs\UpdateComicDTO;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Repositories\ComicRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ComicService
{

    public function __construct(
        private ComicRepository $comicRepository
    ) {}

    const CACHE_TTL = 300;

    public function indexByFilter(IndexComicDTO $indexComicDTO, ?User $user = null)
    {
        $cacheKey = 'comics.index.' . md5(serialize($indexComicDTO->toArray()));

        $comics = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($indexComicDTO, $user) {});

        if ($user) {
            return $this->comicRepository->indexByFilter($indexComicDTO, $user->isWriter());
        } else {
            return $this->comicRepository->indexByFilter($indexComicDTO);
        }

        return $comics;
    }

    public function store(StoreComicDTO $storeComicDTO, int $userId)
    {
        $comic = DB::transaction(function () use ($storeComicDTO, $userId) {
            // Сохраняем обложку, если она есть
            if ($storeComicDTO->cover_image) {
                $newId =  Comic::max('id');
                $path = 'comics/covers/' . $newId  . '.' . $storeComicDTO->cover_image->extension();

                $success = Storage::disk('s3')->putFileAs(
                    'comics/covers/',
                    $storeComicDTO->cover_image,
                    $newId . '.' . $storeComicDTO->cover_image->extension(),
                    'public'
                );

                $url = Storage::disk('s3')->url($path);
                $storeComicDTO->path_cover_image = $url;
            }


            // Добавляем автора
            $storeComicDTO->author_id = $userId;

            return $this->comicRepository->store($storeComicDTO);
        });
        return $comic;
    }

    public function show(Comic $comic)
    {
        // Увеличиваем просмотры
        if ($comic->status === 'published') {
            $comic->incrementViews();
        }

        $cacheKey = "comic.{$comic->id}.full";

        $comic = Cache::remember($cacheKey, 60 * 60, function () use ($comic) {
            return $this->comicRepository->show($comic);
        });

        return $comic;
    }

    public function update(UpdateComicDTO $updateComicDTO, Comic $comic)
    {
        DB::transaction(function () use ($comic, $updateComicDTO) {
            // Обновляем обложку если есть новая
            if ($updateComicDTO->cover_image) {
                // Удаляем старую
                if ($comic->cover_image && Storage::disk('s3')->exists($comic->cover_image)) {
                    Storage::disk('s3')->delete($comic->cover_image);
                }

                $path = 'comics/covers/' . $comic->id . '.' . $updateComicDTO->cover_image->extension();

                $success = Storage::disk('s3')->putFileAs(
                    'comics/covers/',
                    $updateComicDTO->cover_image,
                    $comic->id . '.' . $updateComicDTO->cover_image->extension(),
                    'public'
                );

                if (!$success) {
                    throw new Exception('Не удалось сохранить обложку комикса на сервере');
                }
                $url = Storage::disk('s3')->url($path);
                $updateComicDTO->path_cover_image = $url;
            }
            // Обновляем комикс
            $this->comicRepository->update($updateComicDTO, $comic);
        });
    }

    public function destroy(Comic $comic)
    {
        DB::transaction(function () use ($comic) {
            // Удаляем обложку, если она есть
            if ($comic->cover_image && Storage::disk('s3')->exists($comic->cover_image)) {
                Storage::disk('s3')->delete($comic->cover_image);
            }

            $this->comicRepository->destroy($comic);
        });
    }
}
