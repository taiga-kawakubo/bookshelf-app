<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('books')
            ->orderBy('id')
            ->get();

        return view('genres.index', compact('genres'));
    }

    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }
}
