<?php

namespace Database\Factories;

use App\Models\Comic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Получаем всех пользователей и комиксы
        $users = User::all();
        $comics = Comic::all();

        // Выбираем случайного пользователя и комикс
        $user = $users->random();
        $comic = $comics->random();

        // Определяем оценку в зависимости от типа комикса
        // (можно настроить распределение оценок по типам комиксов)
        $baseRate = $this->getBaseRateForComic($comic);

        // Добавляем случайное отклонение (-1, 0, +1)
        $rate = max(1, min(5, $baseRate + $this->faker->randomElement([-1, 0, 1])));

        return [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'rate' => $rate,
        ];
    }

    /**
     * Получить базовую оценку в зависимости от типа комикса
     */
    private function getBaseRateForComic(Comic $comic): int
    {
        // Распределение оценок по типам комиксов
        $baseRates = [
            'manga' => 4,  // Манга обычно получает высокие оценки
            'manhwa' => 4, // Манхва тоже популярна
            'manhua' => 3, // Маньхуа может быть разного качества
            'western' => 4, // Западные комиксы
            'other' => 3,   // Остальные
        ];

        return $baseRates[$comic->type] ?? 3;
    }

    /**
     * Установить конкретную оценку
     */
    public function rate(int $rate): static
    {
        return $this->state(function (array $attributes) use ($rate) {
            return [
                'rate' => max(1, min(5, $rate)),
            ];
        });
    }

    /**
     * Установить высокую оценку (4-5)
     */
    public function high(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rate' => $this->faker->randomElement([4, 5]),
            ];
        });
    }

    /**
     * Установить среднюю оценку (3)
     */
    public function average(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rate' => 3,
            ];
        });
    }

    /**
     * Установить низкую оценку (1-2)
     */
    public function low(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rate' => $this->faker->randomElement([1, 2]),
            ];
        });
    }
}
