<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Устанавливаем русскую локаль для Faker
        $faker = \Faker\Factory::create('ru_RU');

        // Получаем случайного пользователя и комикс
        $user = User::inRandomOrder()->first();
        $comic = Comic::inRandomOrder()->first();

        // Генерируем русский текст комментария
        $comments = [
            'Отличный комикс! Очень понравился сюжет и персонажи.',
            'Хорошая работа автора, жду продолжения с нетерпением.',
            'Интересный мир и проработанные персонажи. Рекомендую!',
            'Неплохо, но есть над чем поработать. Хотелось бы больше экшена.',
            'Прочитал за один вечер, не мог оторваться!',
            'Стиль рисования очень приятный, история захватывает с первых страниц.',
            'Мне понравилось, но концовка могла бы быть лучше.',
            'Отличное начало, надеюсь автор не бросит проект.',
            'Персонажи живые, с юмором проблем нет. Хороший комикс!',
            'Сюжет немного предсказуем, но в целом достойно.',
            'Арт просто шикарный! Каждая панель как картина.',
            'Прочитал все главы за ночь, очень увлекательно!',
            'Интересная концепция, никогда такого не встречал.',
            'Главный герой вызывает симпатию, хочется следить за его развитием.',
            'Динамика повествования на высоте, скучно не бывает.',
            'Хороший баланс между юмором и драмой.',
            'Мир проработан до мелочей, чувствуется любовь автора к своему творению.',
            'Жду каждое обновление как манны небесной!',
            'Сначала показался скучным, но после 3 главы втянулся.',
            'Отличный вариант для вечернего чтения.',
        ];

        return [
            'user_id' => $user->id,
            'comic_id' => $comic->id,
            'text' => $faker->randomElement($comments),
            'parent_id' => null, // По умолчанию корневой комментарий
            'deleted_at' => $faker->optional(0.05)->dateTime(), // 5% удалены
            'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Indicate that the comment is a reply.
     */
    public function reply(): static
    {
        return $this->state(function (array $attributes) {
            // Получаем случайный корневой комментарий для ответа
            $parent = Comment::whereNull('parent_id')->inRandomOrder()->first();

            return [
                'parent_id' => $parent ? $parent->id : null,
                'comic_id' => $parent ? $parent->comic_id : $attributes['comic_id'],
            ];
        });
    }

    /**
     * Indicate that the comment is a nested reply (reply to reply).
     */
    public function nestedReply(): static
    {
        return $this->state(function (array $attributes) {
            // Получаем случайный комментарий, который уже является ответом
            $parent = Comment::whereNotNull('parent_id')
                ->whereHas('parent', function ($query) {
                    $query->whereNull('parent_id'); // Проверяем, что родитель корневой
                })
                ->inRandomOrder()
                ->first();

            return [
                'parent_id' => $parent ? $parent->id : null,
                'comic_id' => $parent ? $parent->comic->id : $attributes['comic_id'],
            ];
        });
    }

    /**
     * Indicate that the comment is deleted.
     */
    public function deleted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => now(),
            ];
        });
    }
}
