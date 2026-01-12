<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'comic_id',
        'text',
        'parent_id',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
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

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    // ============ SCOPES ============

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    // ============ МЕТОДЫ ============

    public function approve(): bool
    {
        return $this->update(['is_approved' => true]);
    }

    public function reject(): bool
    {
        return $this->update(['is_approved' => false]);
    }

    public function hasReplies(): bool
    {
        return $this->replies()->count() > 0;
    }
}
