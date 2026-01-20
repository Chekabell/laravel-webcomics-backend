<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    protected bool $showFullChapterNumber = false;
    protected bool $showPagesCount = false;
    public function __construct($resource,  $index = null, ?array $options = null,)
    {
        parent::__construct($resource);

        if (is_array($options)) {
            $this->showFullChapterNumber = in_array('full_chapter_number', $options) ? true : false;
            $this->showPagesCount = in_array('pages_count', $options) ? true : false;
        }
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'chapter_number' => $this->when(!$this->showFullChapterNumber, $this->chapter_number),
            'chapter_decimal' => $this->when(!$this->showFullChapterNumber, $this->chapter_decimal),
            'full_chapter_number' => $this->when($this->showFullChapterNumber, $this->full_chapter_number),

            // Статистика
            'pages_count' => $this->when($this->showPagesCount, $this->pages_count),

            // Даты
            'published_at' => $this->published_at->format('Y-m-d H:i:s'),

            // Связи
            'comic' => $this->whenLoaded('comic', function () {
                return [
                    'id' => $this->comic->id,
                    'title' => $this->comic->title,
                ];
            }),

            // Навигация
            'next_chapter' => $this->when(
                $request->has('with_navigation'),
                function () {
                    $next = $this->getNextChapter();
                    return $next ? [
                        'id' => $next->id,
                        'chapter_number' => $next->chapter_number,
                        'title' => $next->title,
                    ] : null;
                }
            ),

            'previous_chapter' => $this->when(
                $request->has('with_navigation'),
                function () {
                    $prev = $this->getPreviousChapter();
                    return $prev ? [
                        'id' => $prev->id,
                        'chapter_number' => $prev->chapter_number,
                        'title' => $prev->title,
                    ] : null;
                }
            ),

            // Прогресс чтения
            'reading_progress' => $this->when(
                $request->user() && $this->relationLoaded('readingProgress'),
                function () use ($request) {
                    $progress = $this->readingProgress
                        ->where('user_id', $request->user()->id)
                        ->first();

                    return $progress ? [
                        'current_page' => $progress->current_page,
                        'read_percentage' => $progress->read_percentage,
                        'is_completed' => $progress->is_completed,
                        'last_read_at' => $progress->last_read_at->format('Y-m-d H:i:s'),
                    ] : null;
                }
            ),
        ];
    }
}
