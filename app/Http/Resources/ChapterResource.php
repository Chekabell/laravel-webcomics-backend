<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    protected bool $showFullChapterNumber = false;
    protected bool $showPagesCount = false;

    // Фабричный метод для создания с параметрами
    public static function makeWithOptions($resource, array $options = []): self
    {
        $instance = new static($resource);
        $instance->showFullChapterNumber = $options['full_chapter_number'] ?? false;
        $instance->showPagesCount = $options['pages_count'] ?? false;
        return $instance;
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

            'pages' => $this->whenLoaded('pages', function () {
                return $this->pages->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'page_number' => $page->page_number,
                        'image_url' => $page->image_url_final,
                        'width' => $page->width,
                        'height' => $page->height,
                        'format' => $page->format,
                        'file_size' => $page->file_size,
                        'file_size_formatted' => $page->file_size_formatted,
                        'dimensions' => $page->getDimensions(),
                    ];
                });
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
