<?php

namespace App\Observers;

use App\Models\Tag;
use Illuminate\Support\Facades\Log;

class TagObserver
{
    /**
     * Handle the Tag "deleting" event.
     */
    public function deleting(Tag $tag): void
    {
        if ($tag->isUsed()) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Тег '{$tag->title}' нельзя удалить, так как он используется в {$tag->usage_count} комиксах"
            );
        }

        Log::info('Tag deleting', ['tag_id' => $tag->id, 'title' => $tag->title]);
    }
}
