<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


/**
 * App\Models\Chapter
 *
 * @property int $id
 * @property string $title
 * @property int $chapter_number
 * @property float|null $chapter_decimal
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property int $comic_id
 */
class Chapter extends Model
{
    protected $fillable = [
        'comic_id',
        'title',
        'chapter_number',
        'chapter_decimal',
        'pages_count',
        'published_at',
    ];

    protected $casts = [
        'chapter_number' => 'integer',
        'chapter_decimal' => 'float',
        'pages_count' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $appends = ['full_chapter_number'];

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

    // ============ МЕТОДЫ ============

    protected static function booted()
    {
        static::saved(function ($chapter) {
            if ($chapter->wasChanged('pages_count')) {
                $chapter->comic->recalculateChapterCounters();
            }
        });

        static::deleted(function ($chapter) {
            $chapter->comic->recalculateChapterCounters();
        });
    }

    public function getNextChapter(): ?self
    {
        return self::where('comic_id', $this->comic_id)
                  ->where('chapter_number', '>', $this->chapter_number)
                  ->orderBy('chapter_number')
                  ->first();
    }

    public function getPreviousChapter(): ?self
    {
        return self::where('comic_id', $this->comic_id)
                  ->where('chapter_number', '<', $this->chapter_number)
                  ->orderBy('chapter_number', 'desc')
                  ->first();
    }

    public function setMeta(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
        $this->save();
    }

    public function getMeta(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }
}
