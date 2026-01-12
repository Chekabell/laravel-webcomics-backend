<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ComicCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => ComicResource::collection($this->collection),

            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'links' => $this->linkCollection()->toArray(),
                'path' => $this->path(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],

            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }

    /**
     * Дополнительные метаданные.
     */
    public function with(Request $request): array
    {
        return [
            'filters' => [
                'type' => $request->get('type'),
                'year' => $request->get('year'),
                'tag' => $request->get('tag'),
                'search' => $request->get('search'),
                'sort' => $request->get('sort', 'newest'),
            ],
        ];
    }

}
