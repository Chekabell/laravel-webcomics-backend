<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'page_number',
        'image_path',
        'image_url',
        'width',
        'height',
        'format',
        'file_size',
        'is_compressed',
        'thumbnail_path',
        'metadata',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'is_compressed' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['image_url_final'];

    // ============ СВЯЗИ ============

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    // ============ АКСЕССОРЫ ============

    protected function imageUrlFinal(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Приоритет: 1) image_url, 2) thumbnail_path, 3) image_path
                if ($this->image_url) {
                    return $this->image_url;
                }

                if ($this->thumbnail_path) {
                    if (filter_var($this->thumbnail_path, FILTER_VALIDATE_URL)) {
                        return $this->thumbnail_path;
                    }
                    return Storage::url($this->thumbnail_path);
                }

                if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
                    return $this->image_path;
                }

                return Storage::url($this->image_path);
            }
        );
    }

    // ============ МЕТОДЫ ============

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

    public function getDimensions(): string
    {
        if ($this->width && $this->height) {
            return "{$this->width}×{$this->height}";
        }
        return 'Unknown';
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size) return 'Unknown';

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
