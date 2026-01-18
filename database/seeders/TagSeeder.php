<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    private $tags = [
        // Основные жанры (10)
        ['title' => 'Приключения', 'description' => 'Путешествия, исследования, поиски'],
        ['title' => 'Фэнтези', 'description' => 'Магия, мифические существа, волшебные миры'],
        ['title' => 'Боевик', 'description' => 'Экшен, драки, перестрелки'],
        ['title' => 'Драма', 'description' => 'Эмоциональные конфликты, глубокие переживания'],
        ['title' => 'Комедия', 'description' => 'Юмор, сатира, развлекательный контент'],
        ['title' => 'Романтика', 'description' => 'Любовные отношения, чувства, свидания'],
        ['title' => 'Школа', 'description' => 'Школьная жизнь, учеба, одноклассники'],
        ['title' => 'Сверхъестественное', 'description' => 'Паранормальные явления, призраки'],
        ['title' => 'Мистика', 'description' => 'Загадки, тайны, необъяснимое'],
        ['title' => 'Хоррор', 'description' => 'Ужасы, страх, напряженная атмосфера'],

        // Научная фантастика (5)
        ['title' => 'Научная фантастика', 'description' => 'Технологии, космос, будущее'],
        ['title' => 'Киберпанк', 'description' => 'Хакеры, неоновые города, кибернетика'],
        ['title' => 'Постапокалипсис', 'description' => 'Мир после катастрофы, выживание'],
        ['title' => 'Космос', 'description' => 'Космические путешествия, планеты, инопланетяне'],
        ['title' => 'Меха', 'description' => 'Гигантские роботы, механические костюмы'],

        // Популярные в азиатских комиксах (10)
        ['title' => 'Исекай', 'description' => 'Попадание в другой мир'],
        ['title' => 'Гарем', 'description' => 'Один главный герой, несколько претендентов'],
        ['title' => 'Магия', 'description' => 'Волшебство, заклинания, магические системы'],
        ['title' => 'Боевые искусства', 'description' => 'Кунг-фу, карате, единоборства'],
        ['title' => 'Перерождение', 'description' => 'Новая жизнь, реинкарнация'],
        ['title' => 'Культивация', 'description' => 'Путь силы, медитация, совершенствование'],
        ['title' => 'Реинкарнация', 'description' => 'Возрождение с памятью о прошлой жизни'],
        ['title' => 'Гильдии', 'description' => 'Союзы, кланы, группировки'],
        ['title' => 'Демоны', 'description' => 'Темные существа, адские силы'],
        ['title' => 'Ангелы', 'description' => 'Небесные существа, божественные силы'],

        // Демографические (4)
        ['title' => 'Сёнэн', 'description' => 'Для юношей, экшен, приключения'],
        ['title' => 'Сёдзё', 'description' => 'Для девушек, романтика, отношения'],
        ['title' => 'Сэйнэн', 'description' => 'Для мужчин, реализм, психология'],
        ['title' => 'Дзёсэй', 'description' => 'Для женщин, повседневность, драма'],

        // Тематические (6)
        ['title' => 'Исторический', 'description' => 'Прошлые эпохи, реальные события'],
        ['title' => 'Спорт', 'description' => 'Спортивные соревнования, тренировки'],
        ['title' => 'Музыка', 'description' => 'Музыкальная индустрия, идолы, группы'],
        ['title' => 'Кулинария', 'description' => 'Приготовление пищи, рецепты, еда'],
        ['title' => 'Игры', 'description' => 'Видеоигры, VR, игровые миры'],
        ['title' => 'Повседневность', 'description' => 'Обычная жизнь без фантастики'],

        // Существа (5)
        ['title' => 'Вампиры', 'description' => 'Кровопийцы, ночные существа'],
        ['title' => 'Оборотни', 'description' => 'Люди-волки, трансформации'],
        ['title' => 'Зомби', 'description' => 'Ожившие мертвецы, апокалипсис'],
        ['title' => 'Супергерои', 'description' => 'Сверхспособности, спасение мира'],
        ['title' => 'Ниндзя', 'description' => 'Скрытные воины, шпионаж'],
    ];

    public function run(): void
    {
        $this->command->info('Создание тегов...');

        $created = 0;
        $skipped = 0;

        foreach ($this->tags as $tagData) {
            $tag = Tag::firstOrCreate(
                ['title' => $tagData['title']],
                $tagData
            );

            if ($tag->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->command->info("✅ Теги созданы: {$created} новых, {$skipped} уже существовали");

        // Добавляем временные метки для реалистичности
        $this->addTimestamps();
    }

    private function addTimestamps(): void
    {
        $tags = Tag::all();
        $now = now();

        foreach ($tags as $tag) {
            $randomDate = $now->copy()->subDays(rand(0, 365));
            $tag->update([
                'created_at' => $randomDate,
                'updated_at' => $randomDate->copy()->addDays(rand(0, 30)),
            ]);
        }

        $this->command->info('✅ Временные метки тегов обновлены');
    }
}
