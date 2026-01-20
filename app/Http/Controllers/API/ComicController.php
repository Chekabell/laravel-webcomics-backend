<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comic\IndexRequest;
use App\Http\Requests\Comic\StoreRequest;
use App\Http\Requests\Comic\UpdateRequest;
use App\Http\Resources\ComicCollection;
use App\Http\Resources\ComicResource;
use App\Models\Comic;
use App\Services\ComicService;
use App\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ComicController extends Controller
{

    public function __construct(
        private TagService $tagService,
        private ComicService $comicService
    ) {}

    /**
     * Получение списка комиксов с фильтрацией.
     */
    public function index(IndexRequest $indexRequest)
    {
        $comics = $this->comicService->indexByFilter($indexRequest->toDTO(), $indexRequest->user());

        return response()->json(
            new ComicCollection($comics->with('tags')->paginate($indexRequest->per_page ?? 5)),
            Response::HTTP_OK
        );
    }

    /**
     * Создание нового комикса (только писатели и админы).
     */
    public function store(StoreRequest $storeRequest)
    {
        //Валидация
        $storeComicDTO = $storeRequest->toDTO();

        //Сохранение комикса
        $comic = $this->comicService->store(
            $storeComicDTO,
            $storeRequest->user()->id
        );

        //Обработка тэгов
        $this->tagService->syncComicTags(
            $comic,
            $storeRequest->input('tags')
        );

        return response()->json(new ComicResource(
                $comic,
                options: [
                    'description',
                    'ratingc_count',
                    'views_count',
                    'comments_count',
                    'chapters_count',
                    'has_chapters',
                    'rating_stars',
                    'published_at',
                    'first_chapter'
                ]), Response::HTTP_CREATED);
    }

    /**
     * Получение информации о комиксе.
     */
    public function show(Request $request, Comic $comic)
    {
        $user = $request->user();

        if ($comic->status === 'draft') {
            // Неавторизованные пользователи не имеют доступа
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $canRead = false;

            if ($user->isWriter() && $user->id === $comic->author_id)
                $canRead = true;
            elseif ($user->isAdmin())
                $canRead = true;

            if (!$canRead) {
                return response()->json(null, Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json(
            new ComicResource(
                $this->comicService->show($comic),
                options: [
                    'description',
                    'ratingc_count',
                    'views_count',
                    'comments_count',
                    'chapters_count',
                    'has_chapters',
                    'rating_stars',
                    'published_at',
                    'first_chapter',
                ]
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Обновление комикса (только автор или админ).
     */
    public function update(UpdateRequest $updateRequest, Comic $comic)
    {
        $canUpdate = false;

        if ($updateRequest->user()->isWriter() && $updateRequest->user()->id === $comic->author_id)
            $canUpdate = true;
        elseif ($updateRequest->user()->isAdmin())
            $canUpdate = true;

        if (!$canUpdate) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $updateComicDTO = $updateRequest->toDTO();

        $this->comicService->update($updateComicDTO, $comic);

        $this->tagService->syncComicTags(
            $comic,
            $updateRequest->input('tags')
        );

        return response()->json(
            new ComicResource(
                $comic->fresh(),
                options: [
                    'description',
                    'ratingc_count',
                    'views_count',
                    'comments_count',
                    'chapters_count',
                    'has_chapters',
                    'rating_stars',
                    'published_at',
                    'first_chapter',
                ]
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Удаление комикса (только автор или админ).
     */
    public function destroy(Request $request, Comic $comic)
    {
        //Проверка доступа
        $canDelete = false;

        if ($request->user()->isWriter() && $request->user()->id === $comic->author_id)
            $canDelete = true;
        elseif ($request->user()->isAdmin())
            $canDelete = true;

        if (!$canDelete) {
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }

        //Удаление
        $this->comicService->destroy($comic);

        //Синхронизация тэгов
        $this->tagService->syncComicTags(
            $comic,
            null
        );

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function publish(Request $request, Comic $comic)
    {
        $canPublish = false;

        if ($request->user()->isWriter() && $request->user()->id === $comic->author_id)
            $canPublish = true;
        elseif ($request->user()->isAdmin())
            $canPublish = true;

        if (!$canPublish) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $comic->publish();

        return response()->json($comic->status, Response::HTTP_OK);
    }

    /**
     * Получение популярных комиксов.
     */
    public function popular()
    {
        $cacheKey = 'comics.popular';

        $comics = Cache::remember($cacheKey, 60 * 60, function () {
            return Comic::published()
                ->orderBy('cached_rating', 'desc')
                ->orderBy('cached_views_count', 'desc')
                ->limit(10)
                ->get(['id', 'title', 'cover_image', 'cached_rating', 'cached_views_count']);
        });

        return response()->json($comics);
    }

    /**
     * Получение новинок.
     */
    public function newest()
    {
        $cacheKey = 'comics.newest';

        $comics = Cache::remember($cacheKey, 60 * 60, function () {
            return Comic::published()
                ->orderBy('published_at', 'desc')
                ->limit(10)
                ->get(['id', 'title', 'cover_image', 'published_at']);
        });

        return response()->json($comics);
    }

    public function featured()
    {
        $cacheKey = 'comics.featured';

        $comics = Cache::remember($cacheKey, 1, function () {
            return Comic::featured()
                ->orderBy('published_at', 'desc')
                ->limit(10)
                ->get();
        });

        return response()->json(ComicResource::collection($comics));
    }
}
