<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookIndexResource;
use App\Http\Resources\Api\V1\BookShowResource;
use App\Http\Resources\Api\V1\BookStoreUpdateResource;
use App\Http\Resources\Api\V1\GenreResource;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 検索・絞り込み・並び替え条件に応じて書籍一覧を取得する。
     *
     * @param  IndexBookRequest  $request  書籍一覧の検索条件
     * @return AnonymousResourceCollection 書籍一覧のAPIレスポンス
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
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

        $perPage = $validated['per_page'] ?? 20;

        $books = $query
            ->paginate($perPage)
            ->withQueryString();

        $genres = Genre::orderBy('name')->get();

        return BookIndexResource::collection($books)
            ->additional([
                'genres' => GenreResource::collection($genres),
            ]);
    }

    /**
     * 認証ユーザーを登録者として書籍を登録する。
     *
     * @param  StoreBookRequest  $request  書籍登録リクエスト
     * @return JsonResponse 登録した書籍情報のAPIレスポンス
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

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
     * 指定された書籍の詳細情報を取得する。
     *
     * @param  Book  $book  詳細を取得する書籍
     * @return BookShowResource 書籍詳細のAPIレスポンス
     */
    public function show(Book $book): BookShowResource
    {
        $book->load([
            'genres:id,name',
            'reviews' => function ($query) {
                $query->with('user:id,name');
                $query->withCount([
                    'likedByUsers as likes_count',
                ]);
            },
        ]);

        return new BookShowResource($book);
    }

    /**
     * 指定された書籍情報とジャンル紐付けを更新する。
     *
     * @param  UpdateBookRequest  $request  書籍更新リクエスト
     * @param  Book  $book  更新対象の書籍
     * @return BookStoreUpdateResource 更新した書籍情報のAPIレスポンス
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
     * 指定された書籍を削除する。
     *
     * @param  Book  $book  削除対象の書籍
     * @return JsonResponse 空のAPIレスポンス
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(null, 204);
    }
}
