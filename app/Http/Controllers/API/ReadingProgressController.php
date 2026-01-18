<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ReadingProgress;
use App\Models\Comic;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadingProgressController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Chapter $chapter, Comic $comic)
    {
        $validated = $request->validate([
            'is_completed' => 'sometimes|boolean',
            'percentage' => 'required|float|min:0|max:1',
            'current_page' => 'required|integer|',
        ]);

        $comic->readingProgress()->create([
            'user_id' => $request->user()->id,
            'chapter_id' => $chapter->id,
            'is_completed' => $validated['is_completed'],
            'read_percentage' => $validated['percentage'],
            'current_page' => $validated['current_page'],
            'last_read_at' => now(),
        ]);

        return response()->json(null, Response::HTTP_CREATED);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReadingProgress $readingProgress)
    {
        $validated = $request->validate([
            'is_completed' => 'sometimes|boolean',
            'percentage' => 'required|float|min:0|max:1',
            'current_page' => 'required|integer|',
        ]);

        $readingProgress->update([
            'is_completed' => $validated['is_completed'],
            'read_percentage' => $validated['percentage'],
            'current_page' => $validated['current_page'],
            'last_read_at' => now(),
        ]);
    }
}
