<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * 指定された書籍にログインユーザーのレビューを登録する
     *
     * @param  StoreReviewRequest  $request  レビュー登録リクエスト
     * @param  Book  $book  レビュー対象の書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;

        $alreadyReviewed = $book->reviews()
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyReviewed) {
            return redirect()
                ->route('books.show', $book)
                ->with(
                    'error',
                    'この書籍はすでにレビューを投稿しています。'
                );
        }

        $book->reviews()->create([
            'user_id' => $userId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    /**
     * 指定されたレビューの編集画面を表示する
     *
     * @param  Review  $review  編集対象のレビュー
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * 指定されたレビューの評価とコメントを更新する
     *
     * @param  UpdateReviewRequest  $request  レビュー更新リクエスト
     * @param  Review  $review  更新対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();
        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * 指定されたレビューを削除する
     *
     * @param  Review  $review  削除対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }
}
