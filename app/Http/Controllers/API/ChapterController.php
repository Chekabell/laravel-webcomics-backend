<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chapter\StoreRequest;
use App\Http\Requests\Chapter\UpdateRequest;
use App\Services\ChapterService;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChapterController extends Controller
{
    public function __construct(
        private ChapterService $chapterService
    ) {}

    /**
     * Получение списка глав комикса.
     */
    public function index(Request $request, Comic $comic)
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

        $query = $comic->chapters()
            ->orderBy('chapter_number', 'desc')
            ->orderBy('chapter_decimal', 'desc')
            ->orderBy('id', 'desc');

        $chapters = $query->paginate($request->per_page ?? 5);

        return response()->json($chapters);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $storeRequest, Comic $comic)
    {
        $canStoreChapter = false;

        if ($storeRequest->user()->isWriter() && $storeRequest->user()->id === $comic->author_id)
            $canStoreChapter = true;
        elseif ($storeRequest->user()->isAdmin())
            $canStoreChapter = true;

        if (!$canStoreChapter) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $chapter = $this->chapterService->store($storeRequest->toDTO(), $comic);

        if (!$chapter) {
            return response()->json([
                'message' => 'Chapter with this number already exists'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(
            new ChapterResource($chapter, options: ['full_chapter_number']),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Comic $comic, int $chapterId)
    {
        if (
            $comic->status !== 'published' &&
            ((!$request->user()?->isWriter() && $comic->author_id !== !$request->user()?->id) ||
                !$request->user()?->isAdmin())
        ) {
            return response()->json(['error' => 'Forbidden'], 403);
        };

        $chapter = $comic->chapters()->find($chapterId);

        if (!$chapter) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Запрашиваемая глава не найдена в этом комиксе.'
            ], 404);
        }

        return response()->json(
            new ChapterResource($chapter->load('pages')),
            Response::HTTP_OK
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $updateRequest, Comic $comic, Chapter $chapter)
    {
        $canUpdateChapter = false;

        if ($updateRequest->user()->isWriter() && $updateRequest->user()->id === $comic->author_id)
            $canUpdateChapter = true;
        elseif ($updateRequest->user()->isAdmin())
            $canUpdateChapter = true;

        if (!$canUpdateChapter) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $updatedChapter = $this->chapterService->update($updateRequest->toDTO(), $comic, $chapter);

        return response()->json(new ChapterResource($updatedChapter));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comic $comic, Chapter $chapter)
    {
        $canDestroyChapter = false;
        if ($request->user()->isWriter() && $request->user()->id === $comic->author_id)
            $canDestroyChapter = true;
        elseif ($request->user()->isAdmin())
            $canDestroyChapter = true;

        if (!$canDestroyChapter) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        //Удаление
        $this->chapterService->destroy($comic, $chapter);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
