<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

class UpdateComicDTO{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?int $year,
        public readonly ?string $type,
        public readonly ?UploadedFile $cover_image,
        public readonly ?array $tags,
        public readonly ?string $status,
        public ?string $path_cover_image = null,
    ) {}
    public static function fromArray(array $data){
        return new self(
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            year: $data['year'] ?? null,
            type: $data['type'] ?? null,
            cover_image: $data['cover_image'] ?? null,
            tags: $data['tags'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(){
        return  [
            'title' => $this->title,
            'description' => $this->description,
            'year' => $this->year,
            'type' => $this->type,
            'cover_image' => $this->path_cover_image,
            'status' => $this->status,
        ];
    }
}
