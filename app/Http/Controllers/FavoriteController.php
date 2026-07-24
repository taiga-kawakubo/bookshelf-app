<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $books = $request
            ->user()
            ->favoriteBooks()
            ->with('genres')
            ->orderBy('books.id')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $result = $request
            ->user()
            ->favoriteBooks()
            ->toggle($book->id);
        $message = count($result['attached']) > 0
            ? 'お気に入りに追加しました。'
            : 'お気に入りを解除しました。';

        return redirect()
            ->route('books.show', $book)
            ->with('success', $message);
    }
}
