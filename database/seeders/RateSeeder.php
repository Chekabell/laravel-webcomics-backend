<?php

namespace Database\Seeders;

use App\Models\Comic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RateSeeder extends Seeder
{
    private const MAX_RATES_PER_COMIC = 50;

    public function run(): void
    {
        $this->command->info('Очистка таблицы оценок...');
        DB::table('rates')->truncate();
        $this->command->info('✅ Таблица оценок очищена');

        $users = User::all();
        $comics = Comic::where('status', 'published')->get(); // Используем where вместо scope

        if ($users->isEmpty() || $comics->isEmpty()) {
            $this->command->error('Недостаточно данных для создания оценок!');
            $this->command->error('Сначала создайте пользователей и комиксы.');
            return;
        }

        $this->command->info("Найдено пользователей: {$users->count()}");
        $this->command->info("Найдено опубликованных комиксов: {$comics->count()}");

        $totalRates = 0;
        $rateEntries = [];

        foreach ($comics as $comic) {
            // Определяем количество оценок для этого комикса
            $maxPossible = min($users->count(), self::MAX_RATES_PER_COMIC);
            $rateCount = $this->calculateRateCount($comic, $maxPossible);

            if ($rateCount === 0) {
                continue;
            }

            // Выбираем случайных пользователей для оценки этого комикса
            $selectedUsers = $users->random(min($rateCount, $users->count()));

            foreach ($selectedUsers as $user) {
                // Определяем оценку
                $rateValue = $this->determineRateValue($comic, $user);

                $rateEntries[] = [
                    'user_id' => $user->id,
                    'comic_id' => $comic->id,
                    'rate' => $rateValue,
                ];

                $totalRates++;

                // Вставляем пачками по 100 записей
                if (count($rateEntries) >= 100) {
                    DB::table('rates')->insert($rateEntries);
                    $rateEntries = [];
                }
            }

            $this->command->info("   Комикс '{$comic->title}': {$rateCount} оценок");
        }

        // Вставляем оставшиеся записи
        if (!empty($rateEntries)) {
            DB::table('rates')->insert($rateEntries);
        }

        // Обновляем кэшированные рейтинги комиксов ОДНИМ ЗАПРОСОМ
        $this->updateAllComicRatings();

        $this->command->info("✅ Создано {$totalRates} оценок");
        $this->command->info('✅ Кэшированные рейтинги комиксов обновлены');
    }

    /**
     * Рассчитать количество оценок для комикса
     */
    private function calculateRateCount(Comic $comic, int $maxPossible): int
    {
        // Базовое случайное количество
        $baseCount = rand(0, $maxPossible);

        // Модификаторы
        $modifiers = [
            $comic->is_featured ? 1.5 : 1.0,
            $comic->cached_chapters_count > 20 ? 1.3 : 1.0,
            $comic->created_at->diffInMonths(now()) < 3 ? 0.7 : 1.0,
            $comic->type === 'manga' ? 1.2 : 1.0,
            $comic->type === 'manhua' ? 0.8 : 1.0,
        ];

        $adjustedCount = (int)($baseCount * array_product($modifiers));

        return max(0, min($maxPossible, $adjustedCount));
    }

    /**
     * Определить значение оценки
     */
    private function determineRateValue(Comic $comic, User $user): int
    {
        // Базовое значение в зависимости от типа комикса
        $baseRates = [
            'manga' => 4,
            'manhwa' => 4,
            'manhua' => 3,
            'western' => 4,
            'other' => 3,
        ];

        $baseRate = $baseRates[$comic->type] ?? 3;

        // Случайное отклонение
        $deviation = $this->getRandomDeviation();

        // Особенности пользователя (заменяем faker на rand)
        if ($user->role === 'admin') {
            $deviation += $this->getRandomElement([0, 0, 1]); // Админы чуть щедрее
        } elseif ($user->role === 'writer') {
            $deviation += $this->getRandomElement([-1, 0, 0]); // Писатели строже
        }

        // Учитываем, понравился бы комикс этому пользователю
        $finalRate = $baseRate + $deviation;

        // Ограничиваем диапазоном 1-5
        return max(1, min(5, $finalRate));
    }

    /**
     * Получить случайный элемент массива (замена faker->randomElement)
     */
    private function getRandomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    /**
     * Получить случайное отклонение для оценки
     */
    private function getRandomDeviation(): int
    {
        // Распределение: чаще всего ставим средние оценки
        $weights = [
            -2 => 5,  // 1 (очень плохо)
            -1 => 20, // 2-3 (плохо)
            0 => 50,  // 3-4 (нормально)
            1 => 20,  // 4-5 (хорошо)
            2 => 5,   // 5 (отлично)
        ];

        $total = array_sum($weights);
        $random = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $deviation => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $deviation;
            }
        }

        return 0;
    }

    /**
     * Обновить кэшированные рейтинги комиксов ОДНИМ ЗАПРОСОМ
     */
    private function updateAllComicRatings(): void
    {
        $this->command->info('Обновление кэшированных рейтингов...');

        DB::statement("
            UPDATE comics
            SET
                cached_rating = COALESCE(
                    (SELECT ROUND(AVG(r.rate)::numeric, 2)
                        FROM rates r
                        WHERE r.comic_id = comics.id),
                    0
                ),
                cached_ratings_count = COALESCE(
                    (SELECT COUNT(*)
                        FROM rates r
                        WHERE r.comic_id = comics.id),
                    0
                ),
                updated_at = NOW()
            WHERE EXISTS (
                SELECT 1 FROM rates WHERE rates.comic_id = comics.id
            )
        ");

        // Также обнуляем рейтинги комиксов без оценок
        DB::table('comics')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('rates')
                      ->whereRaw('rates.comic_id = comics.id');
            })
            ->update([
                'cached_rating' => 0,
                'cached_ratings_count' => 0,
            ]);

        $this->command->info('✅ Кэшированные рейтинги обновлены');
    }
}
