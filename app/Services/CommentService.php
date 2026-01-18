<?php

namespace App\Services;

use App\DTOs\StoreCommentDTO;
use App\DTOs\UpdateCommentDTO;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommentService{
    public function store(StoreCommentDTO $storeCommentDTO, Comic $comic)
    {
        $comment = $comic->comments()->create([
            'user_id' => $storeCommentDTO->user_id,
            'text' => $storeCommentDTO->text,
            'parent_id' => $storeCommentDTO->parent_id,
        ]);

        return $comment;
    }


    public function update(UpdateCommentDTO $updateCommentDTO, Comment $comment)
    {
        $comment->text = $updateCommentDTO->text;

        $comment->update();

        return $comment->fresh();
    }

    public function destroy(Comment $chapter)
    {
        $chapter->delete();
    }
}
