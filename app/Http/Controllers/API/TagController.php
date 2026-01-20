<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Получение списка глав комикса.
     */
    public function index(Request $request, Comic $comic)
    {
        $tags = Tag::orderBy('title','asc')
            ->get();

        return response()->json($tags);
    }
}
