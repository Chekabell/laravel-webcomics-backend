<?php

namespace App\DTOs;

class StoreCommentDTO{
    public function __construct(
        public readonly string $text,
        public readonly ?int $parent_id = null,
        public ?int $user_id = null
    ) {}
    public static function fromArray(array $data){
        return new self(
            text: $data['text'],
        );
    }

    public function toArray(){
        return  [
            'text' => $this->text,
            'parent_id' => $this->parent_id,
            'user_id' => $this->user_id,
        ];
    }
}
