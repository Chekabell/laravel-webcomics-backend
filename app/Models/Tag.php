<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'usage_count'];

    public function comics()
    {
        return $this->belongsToMany(Comic::class, 'comic_tag');
    }

    protected static function booted()
    {
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->title);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('title')) {
                $tag->slug = Str::slug($tag->title);
            }
        });
    }

    public function incrementUsage()
    {
        $this->timestamps = false;
        $this->increment('usage_count');
        $this->timestamps = true;
    }

    public function decrementUsage()
    {
        $this->timestamps = false;
        $this->decrement('usage_count');
        $this->timestamps = true;
    }
}
