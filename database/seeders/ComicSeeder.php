<?php

namespace Database\Seeders;

use App\Models\Comic;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComicSeeder extends Seeder
{
     /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Comic::truncate();

        $this->command->info('Проверка наличия авторов (админов и писателей)...');

        // Проверяем, есть ли авторы (админы или писатели)
        $authors = User::whereIn('role', ['admin', 'writer'])->get();

        if ($authors->isEmpty()) {
            $this->command->warn('Не найдено авторов (админов или писателей).');
            $this->command->warn('Создаю 2 админов и 3 писателей...');

            // Создаём необходимых авторов
            User::factory(2)->admin()->create();
            User::factory(3)->writer()->create();

            $authors = User::whereIn('role', ['admin', 'writer'])->get();
            $this->command->info('✅ Создано авторов: ' . $authors->count());
        } else {
            $this->command->info('✅ Найдено авторов: ' . $authors->count());
        }

        $this->command->info('Создание комиксов...');

        // Создаем комиксы с равным распределением по типам
        $types = ['манга', 'манхва', 'маньхуа', 'западное', 'другое'];
        $totalPerType = 12; // 60 комиксов / 5 типов = 12 каждого типа

        foreach ($types as $type) {
            // Берем случайных авторов для каждого типа
            for ($i = 0; $i < $totalPerType; $i++) {
                $author = $authors->random();

                Comic::factory()->create([
                    'type' => $type,
                    'author_id' => $author->id,
                ]);
            }

            $this->command->info("Создано {$totalPerType} комиксов типа: {$type}");
        }

        // Отмечаем 10 случайных опубликованных комиксов как закрепленные
        $featuredCount = Comic::where('status', 'published')
            ->inRandomOrder()
            ->limit(10)
            ->update(['is_featured' => true]);

        $this->command->info("✅ {$featuredCount} комиксов отмечены как закрепленные");

        // Для некоторых комиксов обновим статистику (опционально, для реалистичности)
        $this->updateSomeComicsStatistics();

        // Статистика
        $publishedCount = Comic::where('status', 'published')->count();
        $draftCount = Comic::where('status', 'draft')->count();

        $this->command->info('📊 Статистика созданных комиксов:');
        $this->command->info("   Всего: 60 комиксов");
        $this->command->info("   Опубликовано: {$publishedCount} ({$this->calculatePercentage($publishedCount, 60)}%)");
        $this->command->info("   Черновиков: {$draftCount} ({$this->calculatePercentage($draftCount, 60)}%)");

        $typeDistribution = [];
        foreach ($types as $type) {
            $count = Comic::where('type', $type)->count();
            $typeDistribution[] = "{$type}: {$count}";
        }
        $this->command->info("   Распределение по типам: " . implode(', ', $typeDistribution));
    }

    /**
     * Обновляем статистику для некоторых комиксов для реалистичности
     */
    private function updateSomeComicsStatistics(): void
    {
        // Для 30% комиксов добавляем некоторую статистику
        $comicsToUpdate = Comic::inRandomOrder()
            ->limit(18) // 30% от 60
            ->get();

        $updatedCount = 0;
        foreach ($comicsToUpdate as $comic) {
            $comic->update([
                'cached_rating' => round(mt_rand(30, 50) / 10, 1), // 3.0 - 5.0
                'cached_ratings_count' => mt_rand(5, 100),
                'cached_views_count' => mt_rand(100, 5000),
                'cached_comments_count' => mt_rand(0, 50),
                'cached_chapters_count' => mt_rand(1, 100),
            ]);
            $updatedCount++;
        }

        $this->command->info("✅ Обновлена статистика для {$updatedCount} комиксов");
    }

    /**
     * Расчет процентов
     */
    private function calculatePercentage(int $part, int $total): float
    {
        return round(($part / $total) * 100, 1);
    }
}
