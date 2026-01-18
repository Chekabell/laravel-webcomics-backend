<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereIn(string $column, array $values)
 * @method static int increment(string $column, int $amount = 1, array $extra = [])
 * @method static int decrement(string $column, int $amount = 1, array $extra = [])
 */
class Tag extends Model
{
    protected $fillable = [
        'title',
        'description',
        'usage_count'
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function comics(): BelongsToMany
    {
        return $this->belongsToMany(Comic::class, 'comic_tag');
    }

    /**
     * Увеличить счетчик использования с защитой от переполнения
     */
    public function incrementUsage(int $amount = 1): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return $this->increment('usage_count', $amount) !== false;
    }

    /**
     * Уменьшить счетчик использования с защитой от отрицательных значений
     */
    public function decrementUsage(int $amount = 1): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $newValue = $this->usage_count - $amount;
        if ($newValue < 0) {
            $this->usage_count = 0;
            return $this->save();
        }

        return $this->decrement('usage_count', $amount) !== false;
    }

    /**
     * Проверить, используется ли тег
     */
    public function isUsed(): bool
    {
        return $this->usage_count > 0;
    }

    /**
     * Проверить, можно ли удалить тег
     */
    public function canBeDeleted(): bool
    {
        return !$this->isUsed();
    }

    /**
     * Получить популярные теги
     */
    public static function popular(int $limit = 10): Collection
    {
        return static::query()
            ->where('usage_count', '>', 0)
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить неиспользуемые теги
     */
    public static function unused(): Collection
    {
        return static::query()
            ->where('usage_count', 0)
            ->get();
    }

    /**
     * Получить теги по частоте использования
     */
    public static function byUsage(string $order = 'desc'): Collection
    {
        return static::query()
            ->orderBy('usage_count', $order)
            ->get();
    }
}
