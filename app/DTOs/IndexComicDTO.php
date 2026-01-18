<?php

namespace App\DTOs;

class IndexComicDTO{
    public function __construct(
        public readonly ?string $type,
        public readonly ?int $yearMax,
        public readonly ?int $yearMin,
        public readonly string $status,
        public readonly ?array $tags,
        public readonly ?string $search,
        public readonly string $sort,
    ) {}

    public static function fromArray(array $data){
        return new self(
            type: $data['type'] ?? null,
            yearMax: $data['yearMax'] ?? null,
            yearMin: $data['yearMin'] ?? null,
            status: $data['status'] ?? 'published',
            tags: $data['tags'] ?? null,
            search: $data['search'] ?? null,
            sort: $data['sort'] ?? 'rating_desc',
        );
    }

    public function toArray(){
        return [
            'type' => $this->type,
            'yearMax' => $this->yearMax,
            'yearMin' => $this->yearMin,
            'status' => $this->status,
            'tags' => $this->tags,
            'search' => $this->search,
            'sort' => $this->sort,
        ];
    }
}
