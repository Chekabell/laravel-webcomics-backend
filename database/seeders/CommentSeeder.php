<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Очистка таблицы комментариев...');
        Comment::query()->delete();
        $this->command->info('✅ Таблица комментариев очищена');

        $users = User::all();
        $comics = Comic::all();

        if ($users->isEmpty() || $comics->isEmpty()) {
            $this->command->error('Недостаточно данных для создания комментариев!');
            return;
        }

        $this->command->info("Найдено пользователей: {$users->count()}");
        $this->command->info("Найдено комиксов: {$comics->count()}");

        $totalComments = 0;
        $commentEntries = [];

        foreach ($comics as $comic) {
            // Определяем количество комментариев для комикса (2-10, больше для популярных)
            $baseCount = $this->calculateCommentCount($comic);

            if ($baseCount === 0) {
                continue;
            }

            $this->command->info("   Комикс '{$comic->title}': создание {$baseCount} комментариев");

            // Создаем корневые комментарии
            $rootCommentsCount = $baseCount;
            $createdRootIds = [];

            for ($i = 0; $i < $rootCommentsCount; $i++) {
                $user = $users->random();
                $text = $this->generateCommentText($comic, $user);

                $commentData = [
                    'user_id' => $user->id,
                    'comic_id' => $comic->id,
                    'text' => $text,
                    'parent_id' => null,
                    'deleted_at' => $this->getRandomDeletionStatus(),
                    'created_at' => $this->getRandomDateForComic($comic),
                    'updated_at' => now(),
                ];

                $commentEntries[] = $commentData;
                $totalComments++;

                // В реальности мы не знаем ID до вставки, поэтому для простоты будем вставлять
                // пачками и потом обновлять связи
            }

            // Вставляем пачку и получаем ID
            if (count($commentEntries) >= 50) {
                $this->insertComments($commentEntries);
                $commentEntries = [];
            }
        }

        // Вставляем оставшиеся комментарии
        if (!empty($commentEntries)) {
            $this->insertComments($commentEntries);
        }

        // Создаем ответы на комментарии
        $this->createReplies();

        // Обновляем счетчик комментариев в комиксах
        $this->updateComicCommentsCount();

        $this->command->info("✅ Создано {$totalComments} комментариев");
        $this->command->info('✅ Счетчики комментариев в комиксах обновлены');
    }

    /**
     * Рассчитать количество комментариев для комикса
     */
    private function calculateCommentCount(Comic $comic): int
    {
        // Базовое количество: 2-10 в зависимости от популярности
        $baseCount = rand(2, 10);

        // Модификаторы:
        $modifiers = [
            $comic->is_featured ? 1.5 : 1.0,           // Закрепленные +50%
            $comic->cached_rating > 4.0 ? 1.3 : 1.0,   // Высокий рейтинг +30%
            $comic->cached_views_count > 1000 ? 1.2 : 1.0, // Много просмотров +20%
            $comic->cached_ratings_count > 50 ? 1.2 : 1.0, // Много оценок +20%
            $comic->created_at->diffInMonths(now()) < 1 ? 0.7 : 1.0, // Новые -30%
        ];

        $adjustedCount = (int)($baseCount * array_product($modifiers));

        return max(2, min(30, $adjustedCount)); // Минимум 2, максимум 30
    }

    /**
     * Сгенерировать текст комментария
     */
    private function generateCommentText(Comic $comic, User $user): string
    {
        $templates = [
            // Позитивные
            "Отличный комикс! Особенно понравился {элемент}. Жду продолжения!",
            "Прочитал все главы за один день. {автор} - гений!",
            "Сюжет затягивает с первых страниц. {персонаж} - мой любимый персонаж.",
            "Арт просто шикарный! Каждая панель как произведение искусства.",
            "Рекомендую всем любителям {жанр}. Не пожалеете!",

            // Критические (но конструктивные)
            "Неплохо, но {критика}. Надеюсь, в следующих главах исправят.",
            "Идея интересная, но исполнение хромает. {предложение}.",
            "Сюжет немного предсказуем. Хотелось бы больше {чего}.",
            "Персонажи как-то плосковаты. Не хватает {чего}.",
            "Читабельно, но не более. {совет}.",

            // Нейтральные
            "Интересная концепция. Буду следить за развитием.",
            "Нормальный комикс для вечернего чтения.",
            "Прочитал из любопытства, остался доволен.",
            "Не мой любимый жанр, но прочел с интересом.",
            "Средненько. Есть как плюсы, так и минусы.",
        ];

        $template = $this->getRandomElement($templates);

        $placeholders = [
            '{элемент}' => $this->getRandomElement(['сюжет', 'арт', 'персонажи', 'мир', 'юмор']),
            '{автор}' => 'Автор',
            '{персонаж}' => $this->getRandomElement(['главный герой', 'антогонист', 'женский персонаж']),
            '{жанр}' => $comic->type === 'manga' ? 'манги' : ($comic->type === 'manhwa' ? 'манхвы' : 'комиксов'),
            '{критика}' => $this->getRandomElement(['темп повествования слишком быстрый', 'диалоги неестественные', 'персонажи стереотипны']),
            '{предложение}' => $this->getRandomElement(['больше проработать персонажей', 'добавить экшена', 'углубить сюжет']),
            '{чего}' => $this->getRandomElement(['неожиданных поворотов', 'глубины персонажей', 'экшена']),
            '{совет}' => $this->getRandomElement(['Можно было бы лучше', 'Есть куда расти', 'Начинающим авторам стоит поучиться']),
        ];

        $text = str_replace(array_keys($placeholders), array_values($placeholders), $template);

        // Добавляем личное обращение для некоторых пользователей
        if ($user->role === 'admin') {
            $prefixes = ["Как администратор, замечу: ", "От лица модерации: "];
            $text = $this->getRandomElement($prefixes) . $text;
        } elseif ($user->role === 'writer') {
            $prefixes = ["Как автор других работ, скажу: ", "С профессиональной точки зрения: "];
            $text = $this->getRandomElement($prefixes) . $text;
        }

        return $text;
    }

    /**
     * Получить случайный элемент массива
     */
    private function getRandomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    /**
     * Получить случайную дату для комментария (от даты создания комикса)
     */
    private function getRandomDateForComic(Comic $comic)
    {
        $start = $comic->created_at;
        $end = now();

        $randomTimestamp = mt_rand($start->timestamp, $end->timestamp);
        return \Carbon\Carbon::createFromTimestamp($randomTimestamp);
    }

    /**
     * Получить случайный статус удаления
     */
    private function getRandomDeletionStatus()
    {
        return rand(1, 100) <= 3 ? now() : null; // 3% удалены
    }

    /**
     * Вставить комментарии пачкой
     */
    private function insertComments(array &$commentEntries): void
    {
        DB::table('comments')->insert($commentEntries);
        $commentEntries = [];
    }

    /**
     * Создать ответы на комментарии
     */
    private function createReplies(): void
    {
        $this->command->info('Создание ответов на комментарии...');

        // Получаем корневые комментарии
        $rootComments = Comment::whereNull('parent_id')->get();
        $users = User::all();

        $replyEntries = [];
        $replyCount = 0;

        foreach ($rootComments as $rootComment) {
            // Определяем, будет ли у этого комментария ответы (30% вероятности)
            if (rand(1, 100) > 30) {
                continue;
            }

            // Количество ответов (1-2)
            $replyNumber = rand(1, 2);

            for ($i = 0; $i < $replyNumber; $i++) {
                $user = $users->where('id', '!=', $rootComment->user_id)->random();
                $text = $this->generateReplyText($rootComment, $user);

                $replyEntries[] = [
                    'user_id' => $user->id,
                    'comic_id' => $rootComment->comic_id,
                    'text' => $text,
                    'parent_id' => $rootComment->id,
                    'deleted_at' => $this->getRandomDeletionStatus(),
                    'created_at' => $this->getRandomDateAfter($rootComment->created_at),
                    'updated_at' => now(),
                ];

                $replyCount++;

                // Вставляем пачками
                if (count($replyEntries) >= 50) {
                    DB::table('comments')->insert($replyEntries);
                    $replyEntries = [];
                }
            }
        }

        // Вставляем оставшиеся ответы
        if (!empty($replyEntries)) {
            DB::table('comments')->insert($replyEntries);
        }

        // Создаем вложенные ответы (ответы на ответы) для некоторых
        $this->createNestedReplies();

        $this->command->info("✅ Создано {$replyCount} ответов на комментарии");
    }

    /**
     * Создать вложенные ответы (ответы на ответы)
     */
    private function createNestedReplies(): void
    {
        $this->command->info('Создание вложенных ответов...');

        // Получаем комментарии-ответы (у которых есть parent_id)
        $replies = Comment::whereNotNull('parent_id')->get();
        $users = User::all();

        $nestedEntries = [];
        $nestedCount = 0;

        foreach ($replies as $reply) {
            // Определяем, будет ли вложенный ответ (10% вероятности)
            if (rand(1, 100) > 10) {
                continue;
            }

            $user = $users->where('id', '!=', $reply->user_id)->random();
            $text = $this->generateNestedReplyText($reply, $user);

            $nestedEntries[] = [
                'user_id' => $user->id,
                'comic_id' => $reply->comic_id,
                'text' => $text,
                'parent_id' => $reply->id,
                'deleted_at' => null, // Вложенные редко удаляют
                'created_at' => $this->getRandomDateAfter($reply->created_at),
                'updated_at' => now(),
            ];

            $nestedCount++;
        }

        // Вставляем вложенные ответы
        if (!empty($nestedEntries)) {
            DB::table('comments')->insert($nestedEntries);
        }

        $this->command->info("✅ Создано {$nestedCount} вложенных ответов");
    }

    /**
     * Сгенерировать текст ответа
     */
    private function generateReplyText(Comment $parent, User $user): string
    {
        $templates = [
            "Согласен с вами! {дополнение}",
            "А мне кажется, что {мнение}. Что вы думаете?",
            "Спасибо за комментарий! {реакция}",
            "Интересная точка зрения. {согласие/несогласие}",
            "Хорошо подмечено! {развитие мысли}",
            "Не совсем согласен. {аргумент}",
            "Тоже обратил на это внимание. {подробность}",
            "Добавлю от себя: {добавка}",
        ];

        $template = $this->getRandomElement($templates);

        $placeholders = [
            '{дополнение}' => $this->getRandomElement(['Особенно понравилось то же самое', 'Хотел бы добавить пару слов', 'Полностью поддерживаю']),
            '{мнение}' => $this->getRandomElement(['автор мог бы сделать лучше', 'это была задумка автора', 'персонаж специально такой']),
            '{реакция}' => $this->getRandomElement(['Рад, что не я один так думаю', 'Приятно видеть единомышленников', 'Спасибо за поддержку']),
            '{согласие/несогласие}' => $this->getRandomElement(['В целом согласен', 'Частично не согласен', 'Поддерживаю эту мысль']),
            '{развитие мысли}' => $this->getRandomElement(['Действительно, стоит задуматься', 'Это важное наблюдение', 'Хорошо, что отметили']),
            '{аргумент}' => $this->getRandomElement(['На мой взгляд, всё иначе', 'Есть контраргументы', 'Можно посмотреть с другой стороны']),
            '{подробность}' => $this->getRandomElement(['Было интересно это заметить', 'Это добавляет глубины', 'Хорошая деталь']),
            '{добавка}' => $this->getRandomElement(['автор явно вложил душу', 'стоит отметить работу художника', 'сюжет развивается логично']),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Сгенерировать текст вложенного ответа
     */
    private function generateNestedReplyText(Comment $parent, User $user): string
    {
        $templates = [
            "Да, вы правы. {согласие}",
            "Уточню: {уточнение}",
            "Интересно, а что если {предположение}",
            "Хороший вопрос. {ответ}",
            "Поддерживаю предыдущего оратора. {поддержка}",
        ];

        $template = $this->getRandomElement($templates);

        $placeholders = [
            '{согласие}' => $this->getRandomElement(['полностью с этим согласен', 'это важное замечание', 'так и есть']),
            '{уточнение}' => $this->getRandomElement(['я имел в виду немного другое', 'можно добавить деталь', 'есть нюанс']),
            '{предположение}' => $this->getRandomElement(['автор задумал это специально', 'в следующей главе будет объяснение', 'это намёк на будущее']),
            '{ответ}' => $this->getRandomElement(['думаю, это так', 'скорее всего, нет', 'время покажет']),
            '{поддержка}' => $this->getRandomElement(['мне тоже так кажется', 'подписываюсь под каждым словом', 'хорошо сказано']),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Получить случайную дату после указанной
     */
    private function getRandomDateAfter(\Carbon\Carbon $date)
    {
        $minutesLater = rand(5, 1440); // От 5 минут до 24 часов
        return $date->copy()->addMinutes($minutesLater);
    }

    /**
     * Обновить счетчик комментариев в комиксах
     */
    private function updateComicCommentsCount(): void
    {
        $this->command->info('Обновление счетчиков комментариев в комиксах...');

        DB::statement("
            UPDATE comics c
            SET
                cached_comments_count = (
                    SELECT COUNT(*)
                    FROM comments com
                    WHERE com.comic_id = c.id
                    AND com.deleted_at IS NULL
                ),
                updated_at = NOW()
        ");

        $this->command->info('✅ Счетчики комментариев обновлены');
    }
}
