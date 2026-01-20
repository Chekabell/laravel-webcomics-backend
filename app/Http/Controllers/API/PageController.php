<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Comic $comic, Chapter $chapter)
    {
        $user = $request->user();

        if ($comic->status === 'draft') {
            // Неавторизованные пользователи не имеют доступа
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $canRead = false;

            if($user->isWriter() && $user->id === $comic->author_id)
                $canRead = true;
            elseif($user->isAdmin())
                $canRead = true;

            if (!$canRead) {
                return response()->json(null, Response::HTTP_FORBIDDEN);
            }
        }

        $query = $chapter->pages()
            ->orderBy('page_number');

        $pages = $query->paginate(1);

        return response()->json($pages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
