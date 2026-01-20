<?php

namespace Database\Seeders;

use App\Models\Comic;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComicTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Очистка таблицы связей комиксов и тегов...');
        DB::table('comic_tag')->truncate();
        $this->command->info('✅ Таблица связей очищена');

        $comics = Comic::all();
        $tags = Tag::all();

        if ($comics->isEmpty() || $tags->isEmpty()) {
            $this->command->error('Недостаточно данных для создания связей!');
            $this->command->error('Сначала создайте комиксы и теги.');
            return;
        }

        $this->command->info("Найдено комиксов: {$comics->count()}");
        $this->command->info("Найдено тегов: {$tags->count()}");

        $totalAttachments = 0;
        $attachments = [];

        // Определяем рекомендуемые теги для каждого типа комикса
        $typeTags = [
            'манга' => ['Сёнэн', 'Приключения', 'Боевые искусства', 'Сверхъестественное', 'Драма', 'Комедия'],
            'манхва' => ['Романтика', 'Драма', 'Фэнтези', 'Перерождение', 'Исекай', 'Гарем'],
            'маньхуа' => ['Боевые искусства', 'Фэнтези', 'Перерождение', 'Исторический', 'Магия', 'Культивация'],
            'западное' => ['Супергерои', 'Научная фантастика', 'Боевик', 'Детектив', 'Хоррор', 'Криминал'],
            'другое' => ['Экспериментальный', 'Повседневность', 'Драма', 'Комедия', 'Философия', 'Психология']
        ];

        foreach ($comics as $comic) {
            // Определяем количество тегов для этого комикса (4-6)
            $tagCount = random_int(4, 6);

            // Получаем теги, подходящие для типа комикса
            $suitableTags = $tags->filter(function($tag) use ($typeTags, $comic) {
                return in_array($tag->title, $typeTags[$comic->type] ?? []);
            });

            // Если подходящих тегов недостаточно, добавляем случайные
            if ($suitableTags->count() < $tagCount) {
                $needed = $tagCount - $suitableTags->count();
                $randomTags = $tags->whereNotIn('id', $suitableTags->pluck('id'))
                    ->random($needed);
                $selectedTags = $suitableTags->merge($randomTags);
            } else {
                $selectedTags = $suitableTags->random($tagCount);
            }

            foreach ($selectedTags as $tag) {
                $attachments[] = [
                    'comic_id' => $comic->id,
                    'tag_id' => $tag->id,
                ];

                $totalAttachments++;

                // Вставляем пачками по 100 записей
                if (count($attachments) >= 100) {
                    DB::table('comic_tag')->insert($attachments);
                    $attachments = [];
                }
            }

            $this->command->info("   Комикс '{$comic->title}': {$tagCount} тегов");
        }

        // Вставляем оставшиеся записи
        if (!empty($attachments)) {
            DB::table('comic_tag')->insert($attachments);
        }

        // Обновляем счетчики использования тегов
        $this->updateTagUsageCounts();

        $this->command->info("✅ Создано {$totalAttachments} связей комиксов с тегами");
        $this->command->info('✅ Счетчики использования тегов обновлены');
    }

    /**
     * Обновить счетчики использования тегов
     */
    private function updateTagUsageCounts(): void
    {
        $this->command->info('Обновление счетчиков использования тегов...');

        DB::statement("
            UPDATE tags t
            SET
                usage_count = (
                    SELECT COUNT(*)
                    FROM comic_tag ct
                    WHERE ct.tag_id = t.id
                )
        ");

        $this->command->info('✅ Счетчики использования тегов обновлены');
    }
}
