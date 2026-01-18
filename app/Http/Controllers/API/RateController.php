<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use App\Models\Comic;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Comic $comic)
    {
        if($comic->status === 'draft'){
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'rate' => 'required|integer|min:1|max:5'
        ]);

        $comic->rates()->create(
            [
                'user_id' => $request->user()->id,
                'comic_id' => $comic->id,
                'rate' => $validated['rate']
            ],
        );

        return response()->json([
            'rating' => $comic->fresh()->cached_rating,
            'ratings_count' => $comic->fresh()->cached_ratings_count
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comic $comic, Rate $rate)
    {
        $validated = $request->validate([
            'rate' => 'required|integer|min:1|max:5'
        ]);

        $rate->update(
            ['rate' => $validated['rate']],
        );

        return response()->json([
            'rating' => $comic->fresh()->cached_rating,
            'ratings_count' => $comic->fresh()->cached_ratings_count
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Rate $rate)
    {
        if($request->user()->id !== $rate->user_id)

        $rate->delete();
        // Observer автоматически обновит cached_rating

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
