<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComicResource extends JsonResource
{
    protected bool $showDescription = false;
    protected bool $showYear = false;
    protected bool $showRatingsCount = false;
    protected bool $showViewsCount = false;
    protected bool $showCommentsCount = false;
    protected bool $showChaptersCount = false;
    protected bool $showHasChapters = false;
    protected bool $showRatingStars = false;
    protected bool $showIsFeatured = false;
    protected bool $showPublishedAt = false;
    protected bool $showCreatedAt = false;
    protected bool $showUpdatedAt = false;
    protected bool $showFirstChapter = false;
    public function __construct($resource,  $index = null, ?array $options = null,)
    {
        parent::__construct($resource);

        if (is_array($options)) {
            $this->showDescription = in_array('description', $options) ? true : false;
            $this->showYear = in_array('year', $options) ? true : false;
            $this->showRatingsCount = in_array('ratings_count', $options) ? true : false;
            $this->showViewsCount = in_array('views_count', $options) ? true : false;
            $this->showCommentsCount = in_array('comments_count', $options) ? true : false;
            $this->showChaptersCount = in_array('chapters_count', $options) ? true : false;
            $this->showHasChapters = in_array('has_chapters', $options) ? true : false;
            $this->showRatingStars = in_array('rating_stars', $options) ? true : false;
            $this->showIsFeatured = in_array('is_featured', $options) ? true : false;
            $this->showPublishedAt = in_array('published_at', $options) ? true : false;
            $this->showCreatedAt = in_array('created_at', $options) ? true : false;
            $this->showUpdatedAt = in_array('updated_at', $options) ? true : false;
            $this->showFirstChapter = in_array('first_chapter', $options) ? true : false;
        }
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->when(
                $this->showDescription,
                $this->description
            ),
            'year' => $this->year,
            'type' => $this->type,

            // Изображения
            'cover_image' => $this->cover_image_url,

            // Рейтинги и статистика
            'rating' => (float) $this->cached_rating,
            'ratings_count' => $this->when(
                $this->showRatingsCount,
                $this->cached_ratings_count
            ),
            'views_count' => $this->when(
                $this->showViewsCount,
                $this->cached_views_count
            ),
            'comments_count' => $this->when(
                $this->showCommentsCount,
                $this->cached_comments_count
            ),
            'chapters_count' => $this->when(
                $this->showChaptersCount && $this->has_chapters,
                $this->cached_chapters_count
            ),
            'has_chapters' => $this->when(
                $this->showHasChapters,
                $this->has_chapters
            ),

            // Рейтинг в звёздах (для фронтенда)
            'rating_stars' => $this->when(
                $this->showRatingStars,
                function () {
                    $rating = (float) $this->cached_rating;
                    $fullStars = floor($rating);
                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

                    return [
                        'full' => $fullStars,
                        'half' => $hasHalfStar,
                        'empty' => $emptyStars,
                    ];
                }
            ),

            // Статус и метаданные
            'status' => $this->when(
                $request->user()?->isWriter() || $request->user()?->id === $this->author_id,
                $this->status
            ),
            'is_featured' => $this->when(
                $this->showIsFeatured,
                $this->is_featured
            ),

            // Даты
            'published_at' => $this->when(
                $this->showPublishedAt,
                $this->published_at?->format('Y-m-d H:i:s')
            ),
            'created_at' => $this->when(
                $this->showCreatedAt,
                $this->created_at?->format('Y-m-d H:i:s')
            ),
            'updated_at' => $this->when(
                $this->showUpdatedAt,
                $this->updated_at?->format('Y-m-d H:i:s')
            ),

            // Связи
            'author' => new UserResource($this->whenLoaded('author')),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(
                    fn($tag) =>
                    TagResource::makeWithOptions($tag)
                );
            }),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),

            // Для навигации
            'first_chapter' => $this->when(
                $this->showFirstChapter,
                $this->first_chapter
            ),

            'last_chapter' => $this->whenLoaded('chapters', function () {
                return $this->last_chapter ? [
                    'id' => $this->last_chapter->id,
                    'chapter_number' => $this->last_chapter->chapter_number,
                ] : null;
            }),
        ];
    }
}
