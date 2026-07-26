<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Resources\Api\V1\BookIndexResource;
use App\Http\Resources\Api\V1\BookShowResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 20;

        $books = Book::query()
            ->with(['genres:id,name'])
            ->withavg('reviews', 'rating')
            ->paginate($perPage)
            ->withQueryString();

        return BookIndexResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * 書籍詳細を取得
     */
    public function show(Book $book)
    {
        $book->load([
            'genres:id,name',
            'reviews.user:id,name',
        ]);

        return new BookShowResource($book);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        //
    }
}
