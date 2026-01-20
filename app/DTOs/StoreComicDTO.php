<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

class StoreComicDTO{
    public function __construct(
        public ?int $author_id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $year,
        public readonly string $type,
        public readonly ?UploadedFile $cover_image,
        public ?string $path_cover_image = null,
        public readonly ?array $tags = null,
        public readonly ?string $status = 'draft',
    ) {}
    public static function fromArray(array $data){
        return new self(
            author_id: $data['author_id'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            year: $data['year'],
            type: $data['type'] ?? 'другое',
            cover_image: $data['cover_image'] ?? null,
            tags: $data['tags'] ?? null,
            status: $data['status'] ?? 'draft',
        );
    }

    public function toArray(){
        return  [
            'author_id' => $this->author_id,
            'title' => $this->title,
            'description' => $this->description,
            'year' => $this->year,
            'type' => $this->type,
            'cover_image' => $this->path_cover_image,
            'status' => $this->status,
        ];
    }
}
