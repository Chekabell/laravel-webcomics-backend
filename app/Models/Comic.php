<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Comic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'title',
        'description',
        'year',
        'type',
        'cover_image',
        'images',
        'external_link',
        'cached_rating',
        'cached_ratings_count',
        'cached_views_count',
        'cached_comments_count',
        'cached_chapters_count',
        'cached_pages_count',
        'metadata',
        'status',
        'is_featured',
        'reading_time',
        'published_at',
    ];

     protected $casts = [
        'year' => 'integer',
        'cached_rating' => 'decimal:2',
        'cached_ratings_count' => 'integer',
        'cached_views_count' => 'integer',
        'cached_comments_count' => 'integer',
        'cached_chapters_count' => 'integer',
        'cached_pages_count' => 'integer',
        'images' => 'array',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'reading_time' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'cover_image_url',
        'has_chapters',
        'avg_pages_per_chapter',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'comic_tag');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('chapter_number');
    }

     public function publishedChapters()
    {
        return $this->hasMany(Chapter::class)
                   ->where('status', 'published')
                   ->orderBy('chapter_number');
    }

    public function rates()
    {
        return $this->hasMany(Rate::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

     // ============ АКСЕССОРЫ ============

    protected function coverImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->cover_image) {
                    return $this->getDefaultCoverUrl();
                }

                if (filter_var($this->cover_image, FILTER_VALIDATE_URL)) {
                    return $this->cover_image;
                }

                return Storage::url($this->cover_image);
            }
        );
    }

    protected function hasChapters(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cached_chapters_count > 0
        );
    }

    protected function avgPagesPerChapter(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->cached_chapters_count == 0) return 0;
                return round($this->cached_pages_count / $this->cached_chapters_count, 1);
            }
        );
    }

    protected function firstChapter(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->publishedChapters()->orderBy('chapter_number')->first()
        );
    }

    protected function lastChapter(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->publishedChapters()->orderBy('chapter_number', 'desc')->first()
        );
    }

    // ============ МЕТОДЫ ============

    protected function getDefaultCoverUrl(): string
    {
        return asset('images/default-comic-cover.jpg');
    }

    public function incrementViews(): void
    {
        $this->timestamps = false;
        $this->increment('cached_views_count');
        $this->timestamps = true;
    }

    public function recalculateRating(): void
    {
        $stats = $this->rates()
            ->selectRaw('COUNT(*) as count, AVG(rate) as avg')
            ->first();

        $this->updateQuietly([
            'cached_ratings_count' => $stats->count ?? 0,
            'cached_rating' => round($stats->avg ?? 0, 2),
        ]);
    }

    public function recalculateChapterCounters(): void
    {
        $publishedChapters = $this->publishedChapters();

        $this->updateQuietly([
            'cached_chapters_count' => $publishedChapters->count(),
            'cached_pages_count' => $publishedChapters->sum('pages_count'),
        ]);
    }

    public function getMeta(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    public function setMeta(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
        $this->save();
    }

    public function publish(): bool
    {
        if ($this->status === 'published') {
            return false;
        }

        return $this->update([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function getReadingProgress(User $user): array
    {
        $progress = $this->readingProgress()
                        ->where('user_id', $user->id)
                        ->first();

        if (!$progress) {
            return [
                'chapter' => $this->firstChapter,
                'page' => 1,
                'percentage' => 0,
                'is_started' => false,
            ];
        }

        return [
            'chapter' => $progress->chapter,
            'page' => $progress->current_page,
            'percentage' => $progress->read_percentage,
            'is_started' => true,
            'last_read_at' => $progress->last_read_at,
        ];
    }

    // ============ SCOPES ============

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopePopular($query)
    {
        return $query->published()
                    ->orderBy('cached_rating', 'desc')
                    ->orderBy('cached_views_count', 'desc');
    }

    public function scopeWithChapters($query)
    {
        return $query->where('cached_chapters_count', '>', 0);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
