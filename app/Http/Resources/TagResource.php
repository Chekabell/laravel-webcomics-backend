<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'usage_count' => $this->usage_count,

            // Статистика
            'comics_count' => $this->whenLoaded('comics', function () {
                return $this->comics->count();
            }),

            // Связи
            'comics' => $this->when(
                $request->has('with_comics') && $this->relationLoaded('comics'),
                ComicResource::collection($this->comics->take(5))
            ),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Дополнительные данные для ответа.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.tags.comics', $this->slug),
            ],
        ];
    }
}
