<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерация тематических ников для читалки комиксов
        $comicNicknames = [
            'MangaReader', 'ComicFan', 'AnimeLover', 'GraphicNovelFan', 'WebtoonReader',
            'BookwormComic', 'StoryAddict', 'DarkManga', 'ComicCollector', 'AnimeEnthusiast',
            'MangaAddict', 'ComicLover', 'AnimeFan', 'GraphicStoryFan', 'WebComicReader',
            'FantasyMangaReader', 'SciFiComicFan', 'SuperheroLover', 'IndieComicFan', 'ClassicMangaCollector',
            'ManhuaFan', 'ManhwaReader', 'ShonenFan', 'ShojoLover', 'SeinenReader', 'JoseiLover',
            'MangaOtaku', 'ComicGeek', 'PanelReader', 'StripCollector'
        ];

        // Случайное добавление чисел к некоторым никам
        $nickname = fake()->randomElement($comicNicknames);
        if (fake()->boolean(40)) {
            $nickname .= fake()->numberBetween(1, 99);
        }

        return [
            'name' => $nickname,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => fake()->optional(0.7)->dateTime(), // 70% верифицированных
            'password' => Hash::make('password'), // Стандартный пароль
            'image' => null,
            'role' => 'reader',
            'remember_token' => Str::random(10),
            'deleted_at' => fake()->optional(0.1)->dateTime(), // 10% удаленных
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
                'password' => Hash::make('admin'), // Пароль для админов
                'email_verified_at' => now(), // Админы всегда верифицированы
            ];
        });
    }

    /**
     * Indicate that the user is a writer.
     */
    public function writer(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'writer',
            ];
        });
    }

    /**
     * Indicate that the user should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the user is soft deleted.
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
