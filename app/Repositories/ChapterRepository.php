<?php

namespace App\Repositories;

use App\DTOs\StoreComicDTO;
use App\DTOs\UpdateComicDTO;
use App\Models\Comic;
use Illuminate\Support\Facades\DB;

class ChapterRepository{

    // public function store(StoreComicDTO $storeComicDTO){
    //     return Chapter::create($storeComicDTO->toArray());
    // }

    public function show(Comic $comic){
        $comic->load([
            'author',
            'tags',
            'chapters'
        ]);
        return $comic;
    }

    public function update(UpdateComicDTO $updateComicDTO, Comic $comic){
        $comic->title = $updateComicDTO->title ?? $comic->title;
        $comic->description = $updateComicDTO->description ?? $comic->description;
        $comic->year = $updateComicDTO->year ?? $comic->year;
        $comic->type = $updateComicDTO->type ?? $comic->type;
        $comic->cover_image = $updateComicDTO->path_cover_image ?? $comic->cover_image;
        $comic->status = $updateComicDTO->status ?? $comic->status;
        return $comic->update();
    }

    public function destroy(Comic $comic){
        $comic->delete();
    }
}
