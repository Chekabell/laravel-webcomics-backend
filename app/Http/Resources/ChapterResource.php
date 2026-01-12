<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
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
            'chapter_number' => $this->chapter_number,
            'chapter_decimal' => $this->chapter_decimal,
            'full_chapter_number' => $this->full_chapter_number,
            'slug' => $this->slug,
            'description' => $this->description,

            // Статистика
            'pages_count' => $this->pages_count,
            'views_count' => $this->views_count,

            // Статус
            'status' => $this->when(
                $request->user()?->isWriter() || $request->user()?->id === $this->comic->author_id,
                $this->status
            ),
            'is_published' => $this->is_published,
            'is_free' => $this->is_free,
            'price' => $this->price,

            // Метаданные
            'metadata' => $this->when(
                $request->user()?->isWriter() || $request->user()?->id === $this->comic->author_id,
                $this->metadata
            ),

            // Даты
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Связи
            'comic' => $this->whenLoaded('comic', function () {
                return [
                    'id' => $this->comic->id,
                    'title' => $this->comic->title,
                    'type' => $this->comic->type,
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

    /**
     * Дополнительные данные для ответа.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.chapters.show', $this->id),
                'comic' => route('api.comics.show', $this->comic_id),
                'pages' => route('api.chapters.pages', $this->id),
            ],
        ];
    }
}
