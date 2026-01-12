<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'comic_id',
        'title',
        'chapter_number',
        'chapter_decimal',
        'slug',
        'description',
        'pages_count',
        'views_count',
        'status',
        'published_at',
        'is_free',
        'price',
        'metadata',
    ];

    protected $casts = [
        'chapter_number' => 'integer',
        'chapter_decimal' => 'float',
        'pages_count' => 'integer',
        'views_count' => 'integer',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = ['full_chapter_number', 'is_published'];

    // ============ СВЯЗИ ============

    public function comic()
    {
        return $this->belongsTo(Comic::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class)->orderBy('page_number');
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    // ============ АКСЕССОРЫ ============

    protected function fullChapterNumber(): Attribute
    {
        return Attribute::make(
            get: function () {
                $number = $this->chapter_number;
                if ($this->chapter_decimal) {
                    $number .= '.' . $this->chapter_decimal;
                }
                return (float) $number;
            }
        );
    }

    protected function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'published'
        );
    }

    // ============ МЕТОДЫ ============

    protected static function booted()
    {
        static::creating(function ($chapter) {
            if (empty($chapter->slug)) {
                $comicSlug = Str::slug($chapter->comic->title);
                $chapterSlug = 'chapter-' . $chapter->chapter_number;
                if ($chapter->chapter_decimal) {
                    $chapterSlug .= '-' . $chapter->chapter_decimal;
                }
                $chapter->slug = $comicSlug . '-' . $chapterSlug;
            }
        });

        static::saved(function ($chapter) {
            if ($chapter->wasChanged('pages_count')) {
                $chapter->comic->recalculateChapterCounters();
            }
        });

        static::deleted(function ($chapter) {
            $chapter->comic->recalculateChapterCounters();
        });
    }

    public function incrementViews(): void
    {
        $this->timestamps = false;
        $this->increment('views_count');
        $this->timestamps = true;
    }

    public function publish(): bool
    {
        if ($this->status === 'published') {
            return false;
        }

        $result = $this->update([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
        ]);

        if ($result) {
            $this->comic->recalculateChapterCounters();
        }

        return $result;
    }

    public function getNextChapter(): ?self
    {
        return self::where('comic_id', $this->comic_id)
                  ->where('status', 'published')
                  ->where('chapter_number', '>', $this->chapter_number)
                  ->orderBy('chapter_number')
                  ->first();
    }

    public function getPreviousChapter(): ?self
    {
        return self::where('comic_id', $this->comic_id)
                  ->where('status', 'published')
                  ->where('chapter_number', '<', $this->chapter_number)
                  ->orderBy('chapter_number', 'desc')
                  ->first();
    }

    public function getMeta(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }
}
