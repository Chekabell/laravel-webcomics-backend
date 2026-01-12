<?php

namespace App\Observers;

use App\Models\Chapter;

class ChapterObserver
{
    public function saved(Chapter $chapter): void
    {
        $chapter->comic->observer->chapterChanged($chapter->comic);
    }

    public function deleted(Chapter $chapter): void
    {
        $chapter->comic->observer->chapterChanged($chapter->comic);
    }
}
