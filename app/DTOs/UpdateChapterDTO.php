<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

class UpdateChapterDTO{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $chapter_number,
        public readonly ?string $new_chapter_number,
        public readonly ?string $chapter_decimal,
        public readonly ?string $new_chapter_decimal,
        public readonly ?UploadedFile $pages_zip,
        public bool $thisChapterAlreadyExists = false,
    ) {}
    public static function fromArray(array $data){
        return new self(
            title: $data['title'] ?? null,
            chapter_number: $data['chapter_number'] ?? null,
            new_chapter_number: $data['new_chapter_number'] ?? null,
            chapter_decimal: $data['chapter_decimal'] ?? null,
            new_chapter_decimal: $data['new_chapter_decimal'] ?? null,
            pages_zip: $data['pages_zip'] ?? null,
        );
    }

    public function toArray(){
        return  [
            'title' => $this->title,
            'chapter_number' => $this->new_chapter_number ?? $this->chapter_number,
            'chapter_decimal' => $this->new_chapter_decimal ?? $this->chapter_decimal,
        ];
    }
}
