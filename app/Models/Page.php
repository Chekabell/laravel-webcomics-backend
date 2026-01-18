<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    protected $fillable = [
        'chapter_id',
        'page_number',
        'image',
        'width',
        'height',
        'format',
        'file_size',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
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
                if($this->image){
                    Storage::disk('s3')->url('default/default-chapter.png');
                }
                return $this->image;
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
