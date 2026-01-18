<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

class StoreChapterDTO{
    public function __construct(
        public readonly string $title,
        public readonly string $chapter_number,
        public readonly ?string $chapter_decimal,
        public readonly UploadedFile $pages_zip,
        public readonly string $published_at,
        public bool $thisChapterAlreadyExists = false,
    ) {}
    public static function fromArray(array $data){
        return new self(
            title: $data['title'],
            chapter_number: $data['chapter_number'],
            chapter_decimal: $data['chapter_decimal'] ?? null,
            pages_zip: $data['pages_zip'],
            published_at: now(),
        );
    }

    public function toArray(){
        return  [
            'title' => $this->title,
            'chapter_number' => $this->chapter_number,
            'chapter_decimal' => $this->chapter_decimal,
            'published_at' => $this->published_at,
        ];
    }
}
