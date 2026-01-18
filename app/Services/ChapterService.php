<?php

namespace App\Services;

use App\DTOs\StoreChapterDTO;
use App\DTOs\UpdateChapterDTO;
use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Support\Facades\DB;
use App\Repositories\ChapterRepository;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class ChapterService{

     public function __construct(
        private ChapterRepository $chapterRepository
    ) {}

    public function store(StoreChapterDTO $storeChapterDTO, Comic $comic){
        // Проверка уникальности номера главы
        $exists = $comic->chapters()
            ->where('chapter_number', $storeChapterDTO->chapter_number)
            ->where('chapter_decimal', $storeChapterDTO->chapter_decimal ?? null)
            ->exists();

        if ($exists) {
            return null;
        }

        $chapter = DB::transaction(function () use ($comic, $storeChapterDTO) {
            $chapter = $comic->chapters()->create($storeChapterDTO->toArray());

            // Обработка ZIP и создание страниц
            $this->processChapterPages($chapter, $storeChapterDTO->pages_zip);

            return $chapter;
        });

        return $chapter;
    }

    // public function show(Comic $comic){
    //     // Увеличиваем просмотры
    //     if ($comic->status === 'published'){
    //         $comic->incrementViews();
    //     }

    //     $cacheKey = "comic.{$comic->id}.full";

    //     $comic = Cache::remember($cacheKey, 60*60, function () use ($comic) {
    //         return $this->comicRepository->show($comic);
    //     });

    //     return $comic;
    // }

    public function update(UpdateChapterDTO $updateChapterDTO, Comic $comic, Chapter $chapter){
        // Проверка наличия главы с новым присланным номером
        if($updateChapterDTO->new_chapter_number || $updateChapterDTO->new_chapter_decimal){
            $newNumberExists = $comic->chapters()
                ->where('chapter_number', $updateChapterDTO->new_chapter_number)
                ->where('chapter_decimal', $updateChapterDTO->new_chapter_decimal ?? null)
                ->exists();

            if ($newNumberExists) {
                throw new Exception('Chapter with this number already exists');
            }
        }

        $updateChapter = DB::transaction(function () use ($chapter, $updateChapterDTO) {
            $chapter->title = $updateChapterDTO->title ?? $chapter->title;
            $chapter->chapter_number = $updateChapterDTO->new_chapter_number ?? $chapter->chapter_number;
            $chapter->chapter_decimal = $updateChapterDTO->new_chapter_decimal ?? $chapter->chapter_decimal;

            if ($updateChapterDTO->pages_zip){
                // Обработка ZIP и создание страниц
                $this->processChapterPages($chapter, $updateChapterDTO->pages_zip);
            }

            $chapter->update();

            return $chapter;
        });

        return $updateChapter;
    }

    public function destroy(Comic $comic, Chapter $chapter){

        DB::transaction(function () use ($chapter) {
            if(Storage::disk('public')
                ->exists("comics/chapters/{$chapter->comic_id}/{$chapter->id}"))
            {
                Storage::disk('public')
                ->deleteDirectory("comics/chapters/{$chapter->comic_id}/{$chapter->id}");

                $chapter->pages()->forceDelete();
            }

            $chapter->delete();
        });
    }

        /**
     * Обрабатывает ZIP архив с картинками и создает страницы главы
     */
    private function processChapterPages(Chapter $chapter, UploadedFile $zipFile): void
    {
        // Сохраняем ZIP во временную папку
        $tempZipPath = $zipFile->store('temp_zips', 'local');

        // Получаем полный путь через Storage
        $fullPath = Storage::disk('local')->path($tempZipPath);

        $zip = new ZipArchive;
        if ($zip->open($fullPath) !== TRUE) {
            Storage::disk('local')->delete($tempZipPath);
            throw new Exception('Cannot open ZIP archive');
        }

        $pages = [];

        // Извлекаем и сортируем файлы по номеру
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++)
        {
            $filename = $zip->getNameIndex($i);

            // Пропускаем директории и скрытые файлы
            if ($filename[strlen($filename) - 1] === '/' || str_starts_with(basename($filename), '.'))
            {
                continue;
            }

            // Проверяем расширение
            if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $filename))
            {
                // Извлекаем номер страницы
                preg_match('/(\d+)\./', basename($filename), $matches);
                $pageNumber = isset($matches[1]) ? (int)$matches[1] : null;

                if (!$pageNumber)
                {
                    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
                    $pageNumber = is_numeric($nameWithoutExt) ? (int)$nameWithoutExt : $i + 1;
                }

                $files[] =
                [
                    'index' => $i,
                    'filename' => $filename,
                    'page_number' => $pageNumber,
                ];
            }
        }

        // Сортируем по номеру страницы
        usort($files, fn($a, $b) => $a['page_number'] <=> $b['page_number']);

        if(Storage::disk('s3')
            ->exists("comics/chapters/{$chapter->comic_id}/{$chapter->id}"))
        {
            Storage::disk('s3')
            ->deleteDirectory("comics/chapters/{$chapter->comic_id}/{$chapter->id}");

            $chapter->pages()->forceDelete();
        }

        // Создаем страницы
        foreach ($files as $file) {
            $imageContent = $zip->getFromIndex($file['index']);

            if (!$imageContent) {
                continue;
            }

            $extension = pathinfo($file['filename'], PATHINFO_EXTENSION);
            $newFilename = "{$file['index']}_" . ".{$extension}";

            // Сохраняем в публичное хранилище
            $storagePath = "comics/chapters/{$chapter->comic_id}/{$chapter->id}/{$newFilename}";
            Storage::disk('s3')->put($storagePath, $imageContent, 'public');

            $pages[] = [
                'chapter_id' => $chapter->id,
                'page_number' => $file['page_number'],
                'image' => Storage::disk('s3')->url($storagePath),
                'file_size' => strlen($imageContent),
            ];
        }

        $zip->close();
        Storage::disk('local')->delete($tempZipPath);

        if (empty($pages)) {
            throw new Exception('No valid image files found in ZIP archive');
        }

        // Массовое создание страниц
        DB::table('pages')->insert($pages);

        // Обновляем счетчик страниц
        $chapter->update(['pages_count' => count($pages)]);

        // Обновляем общий счетчик страниц комикса
        $chapter->comic->recalculateChapterCounters();
    }
}
