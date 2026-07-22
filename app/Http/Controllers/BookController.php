<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(): View
    {
        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->paginate(10);
        
        return view('books.index', compact('books'));
    }

    public function create():View
    {
        $genres = Genre::query()->get();

        return view('books.create', compact('genres'));
    }


    public function store(StoreBookRequest $request):RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function() use($request, $validated){
            $book = Book::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'author'=> $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->attach($validated['genres']);
        });

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を登録しました。');
    }


    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]); 
    
        return view('books.show', compact('book'));
    }
}
