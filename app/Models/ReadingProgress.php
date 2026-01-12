<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comic_id',
        'chapter_id',
        'current_page',
        'read_percentage',
        'is_completed',
        'last_read_at',
    ];

    protected $casts = [
        'current_page' => 'integer',
        'read_percentage' => 'float',
        'is_completed' => 'boolean',
        'last_read_at' => 'datetime',
    ];

    // ============ СВЯЗИ ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comic()
    {
        return $this->belongsTo(Comic::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    // ============ МЕТОДЫ ============

    public function updateProgress(int $pageNumber, int $totalPages): void
    {
        $this->current_page = $pageNumber;
        $this->read_percentage = round(($pageNumber / $totalPages) * 100, 2);
        $this->is_completed = $pageNumber >= $totalPages;
        $this->last_read_at = now();
        $this->save();
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'read_percentage' => 100,
            'is_completed' => true,
            'last_read_at' => now(),
        ]);
    }

    public function reset(): void
    {
        $this->update([
            'current_page' => 1,
            'read_percentage' => 0,
            'is_completed' => false,
        ]);
    }
}
