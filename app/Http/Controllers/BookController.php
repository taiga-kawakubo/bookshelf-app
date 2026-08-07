<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧の表示
     */
    public function index(IndexBookRequest $request): View
    {
        $query = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating');

        $validated = $request->validated();

        // タイトル・著者名検索
        if (! empty($validated['keyword'])) {
            $keyword = $validated['keyword'];

            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('author', 'like', '%'.$keyword.'%')
                    ->orWhereRaw('CONCAT(title, author) LIKE ?', ['%'.$keyword.'%'])
                    ->orWhereRaw("CONCAT(title, ' ', author) LIKE ?", ['%'.$keyword.'%'])
                    ->orWhereRaw("CONCAT(title, '　', author) LIKE ?", ['%'.$keyword.'%'])
                    ->orWhereRaw('CONCAT(author, title) LIKE ?', ['%'.$keyword.'%'])
                    ->orWhereRaw("CONCAT(author, ' ', title) LIKE ?", ['%'.$keyword.'%'])
                    ->orWhereRaw("CONCAT(author, '　', title) LIKE ?", ['%'.$keyword.'%']);
            });
        }

        // ジャンルフィルタ
        if (! empty($validated['genre'])) {
            $genreId = $validated['genre'];

            $query->whereHas('genres', function ($query) use ($genreId) {
                $query->where('genres.id', $genreId);
            });
        }

        // ソート
        $sort = $validated['sort'] ?? 'newest';

        if ($sort === 'title') {
            $query->orderBy('title', 'asc');
        } elseif ($sort === 'rating') {
            $query->orderByRaw('reviews_avg_rating IS NULL ASC')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('created_at');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $books = $query->paginate(10)->withQueryString();

        $genres = Genre::orderBy('name')->get();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍の作成画面を表示
     */
    public function create(): View
    {
        $genres = Genre::query()->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍の登録
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            $book = Book::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'author' => $validated['author'],
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

    /**
     * 書籍詳細の表示
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍の編集画面を表示
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);
        $book->load('genres');
        $genres = Genre::query()->get();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍の更新
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();
        DB::transaction(function () use ($book, $validated) {
            $book->update([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'],
                'published_date' => $validated['published_date'],
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍の削除
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
