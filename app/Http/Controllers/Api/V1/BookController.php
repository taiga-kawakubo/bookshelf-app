<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookIndexResource;
use App\Http\Resources\Api\V1\BookShowResource;
use App\Http\Resources\Api\V1\BookStoreUpdateResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

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
            ->withAvg('reviews', 'rating')
            ->paginate($perPage)
            ->withQueryString();

        return BookIndexResource::collection($books);
    }

    /**
     * 書籍を登録
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($validated) {
            $genreIds = $validated['genres'];
            unset($validated['genres']);

            $book = Book::create($validated);
            $book->genres()->sync($genreIds);

            return $book->load('genres:id,name');
        });

        return (new BookStoreUpdateResource($book))
            ->additional([
                'message' => '書籍を登録しました。',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍詳細を取得
     */
    public function show(Book $book): BookShowResource
    {
        $book->load([
            'genres:id,name',
            'reviews.user:id,name',
        ]);

        return new BookShowResource($book);
    }

    /**
     * 書籍を更新
     */
    public function update(UpdateBookRequest $request, Book $book): BookStoreUpdateResource
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($validated, $book) {
            $genreIds = $validated['genres'];
            unset($validated['genres']);

            $book->update($validated);
            $book->genres()->sync($genreIds);

            return $book->load('genres:id,name');
        });

        return (new BookStoreUpdateResource($book))
            ->additional([
                'message' => '書籍を更新しました。',
            ]);
    }

    /**
     * 書籍の削除
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(null, 204);
    }
}
