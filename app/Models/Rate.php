<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'comic_id', 'rate'];

    protected $casts = [
        'rate' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comic()
    {
        return $this->belongsTo(Comic::class);
    }

    protected static function booted()
    {
        static::saved(function ($rate) {
            $rate->comic->recalculateRating();
        });

        static::deleted(function ($rate) {
            $rate->comic->recalculateRating();
        });
    }
}
