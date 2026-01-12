<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ComicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'year' => $this->year,
            'type' => $this->type,

            // Изображения
            'cover_image' => $this->cover_image,
            'cover_image_url' => $this->getCoverImageUrl(),
            'images' => $this->when($this->images, $this->images),
            'image_gallery' => $this->when($this->images, $this->image_gallery),
            'external_link' => $this->external_link,

            // Рейтинги и статистика
            'rating' => (float) $this->cached_rating,
            'ratings_count' => $this->cached_ratings_count,
            'views_count' => $this->cached_views_count,
            'comments_count' => $this->cached_comments_count,
            'chapters_count' => $this->cached_chapters_count,
            'pages_count' => $this->cached_pages_count,
            'avg_pages_per_chapter' => $this->avg_pages_per_chapter,
            'has_chapters' => $this->has_chapters,

            // Рейтинг в звёздах (для фронтенда)
            'rating_stars' => $this->when($request->has('with_rating_stars'), function () {
                $rating = (float) $this->cached_rating;
                $fullStars = floor($rating);
                $hasHalfStar = ($rating - $fullStars) >= 0.5;
                $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

                return [
                    'full' => $fullStars,
                    'half' => $hasHalfStar,
                    'empty' => $emptyStars,
                ];
            }),

            // Статус и метаданные
            'status' => $this->when(
                $request->user()?->isWriter() || $request->user()?->id === $this->author_id,
                $this->status
            ),
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'reading_time' => $this->reading_time,
            'reading_time_formatted' => $this->reading_time_formatted,
            'metadata' => $this->when(
                $request->user()?->isWriter() || $request->user()?->id === $this->author_id,
                $this->metadata
            ),

            // Даты
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Связи
            'author' => new UserResource($this->whenLoaded('author')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'chapters' => $this->whenLoaded('chapters', function () {
                return $this->chapters->map(function ($chapter) {
                    return [
                        'id' => $chapter->id,
                        'title' => $chapter->title,
                        'chapter_number' => $chapter->chapter_number,
                        'chapter_decimal' => $chapter->chapter_decimal,
                        'full_chapter_number' => $chapter->full_chapter_number,
                        'pages_count' => $chapter->pages_count,
                        'is_free' => $chapter->is_free,
                        'is_published' => $chapter->is_published,
                        'published_at' => $chapter->published_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),

            // Для навигации
            'first_chapter' => $this->whenLoaded('chapters', function () {
                return $this->first_chapter ? [
                    'id' => $this->first_chapter->id,
                    'chapter_number' => $this->first_chapter->chapter_number,
                ] : null;
            }),

            'last_chapter' => $this->whenLoaded('chapters', function () {
                return $this->last_chapter ? [
                    'id' => $this->last_chapter->id,
                    'chapter_number' => $this->last_chapter->chapter_number,
                ] : null;
            }),

            // Прогресс чтения для текущего пользователя
            'reading_progress' => $this->when(
                $request->user(),
                function () use ($request) {
                    return $this->getReadingProgress($request->user());
                }
            ),
        ];
    }

    /**
     * Получить URL обложки.
     */
    protected function getCoverImageUrl(): string
    {
        if (!$this->cover_image) {
            return $this->getDefaultCoverUrl();
        }

        if (filter_var($this->cover_image, FILTER_VALIDATE_URL)) {
            return $this->cover_image;
        }

        return Storage::url($this->cover_image);
    }

    /**
     * URL дефолтной обложки.
     */
    protected function getDefaultCoverUrl(): string
    {
        return asset('images/default-comic-cover.jpg');
        // Или по типу комикса:
        // return asset("images/default-cover-{$this->type}.jpg");
    }

    /**
     * Дополнительные данные для ответа.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.comics.show', $this->id),
                'chapters' => route('api.comics.chapters', $this->id),
                'comments' => route('api.comics.comments', $this->id),
            ],

            'meta' => [
                'version' => '1.0',
                'available_includes' => [
                    'author',
                    'tags',
                    'chapters',
                    'rates',
                    'comments',
                ],
            ],
        ];
    }
}
