<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreRequest;
use App\Http\Requests\Comment\UpdateRequest;
use App\Models\Comic;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService,
    ) {}

    public function index(Request $request, Comic $comic){
        if(!$comic->isPublished()){
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }

        $query = $comic->comments()
            ->with('user:id,name,image')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');


        $comments = $query->paginate($request->per_page ?? 10);

        return response()->json($comments);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request, Comic $comic)
    {
        if(!$comic->isPublished()){
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }

        $data = $request->toDTO();
        $data->user_id = $request->user()->id;

        $comment = $this->commentService->store($data, $comic);

        // Observer автоматически обновит cached_comments_count

        return response()->json([
            'comment' => $comment->load('user')
        ], Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $updateRequest, Comment $comment)
    {
        if($updateRequest->user()->id !== $comment->user_id)
        {
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }

        $comment = $this->commentService->update($updateRequest->toDTO(), $comment);

        return response()->json([
            'comment' => $comment->load('user')
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $comment)
    {
        $canDestroyComment = false;

        if($request->user()->id === $comment->user_id )
            $canDestroyComment = true;
        if($request->user()->isAdmin())
            $canDestroyComment = true;

        if(!$canDestroyComment)
            return response()->json(null, Response::HTTP_FORBIDDEN);

        $this->commentService->destroy( $comment);

        return response()->json( null, Response::HTTP_NO_CONTENT);
    }
}
