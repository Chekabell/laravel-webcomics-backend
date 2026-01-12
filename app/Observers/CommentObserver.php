<?php

namespace App\Observers;

use App\Models\Comment;

class CommentObserver
{
    public function saved(Comment $comment): void
    {
        $comment->comic->observer->commentChanged($comment->comic);
    }

    public function deleted(Comment $comment): void
    {
        $comment->comic->observer->commentChanged($comment->comic);
    }
}
