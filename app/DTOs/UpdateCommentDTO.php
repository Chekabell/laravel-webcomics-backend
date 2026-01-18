<?php

namespace App\DTOs;

class UpdateCommentDTO{
    public function __construct(
        public readonly string $text,
    ) {}
    public static function fromArray(array $data){
        return new self(
            text: $data['text'],
        );
    }

    public function toArray(){
        return  [
            'text' => $this->text,
        ];
    }
}
