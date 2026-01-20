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
        'cached_rating',
        'cached_ratings_count',
        'cached_views_count',
        'cached_comments_count',
        'cached_chapters_count',
        'metadata',
        'status',
        'is_featured',
        'published_at',
    ];

     protected $casts = [
        'year' => 'integer',
        'cached_rating' => 'decimal:2',
        'cached_ratings_count' => 'integer',
        'cached_views_count' => 'integer',
        'cached_comments_count' => 'integer',
        'cached_chapters_count' => 'integer',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'cover_image_url',
        'has_chapters',
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
        return $this->hasMany(Chapter::class);
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
                if(!$this->cover_image){
                    return Storage::disk('s3')->url('default/default-cover.webp');
                }

                return $this->cover_image;
            }
        );
    }

    protected function hasChapters(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cached_chapters_count > 0
        );
    }

    protected function firstChapter(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->chapters()->orderBy('chapter_number')->first()
        );
    }

    protected function lastChapter(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->chapters()->orderBy('chapter_number', 'desc')->first()
        );
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
        $publishedChapters = $this->chapters();

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

    public function isPublished(): bool
    {
        if ($this->status === 'published') {
            return true;
        }
        return false;
    }

    // ============ SCOPES ============

    public function scopeSearch($query, string $searchTerm)
    {
        if (strlen($searchTerm) <= 2) {
            return $query->where('title', 'ILIKE', $searchTerm . '%');
        } else {
            return $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'ILIKE', '%' . $searchTerm . '%') // Точная подстрока
                ->orWhereRaw('title % ?', [$searchTerm]); // Триграммы для опечаток
            })->orderByRaw('
                CASE
                    WHEN title ILIKE ? THEN 1
                    WHEN title % ? THEN 2
                    ELSE 3
                END
            ', ['%' . $searchTerm . '%', $searchTerm]);
        }
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
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

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
