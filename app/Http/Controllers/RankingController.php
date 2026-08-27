<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * レビュー評価に応じた書籍ランキング画面を表示する。
     *
     * @return View 書籍ランキング画面
     */
    public function index(): View
    {
        $rankedBooks = Book::query()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->has('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('updated_at')
            ->orderBy('title')
            ->take(10)
            ->get();

        $currentRank = 0;
        $previousAverageRating = null;

        $rankedBooks->each(function (Book $book) use (&$currentRank, &$previousAverageRating): void {
            $averageRating = (float) $book->reviews_avg_rating;

            if (
                $previousAverageRating === null
                || $averageRating !== $previousAverageRating
            ) {
                $currentRank++;
                $previousAverageRating = $averageRating;
            }

            $book->setAttribute('rank', $currentRank);
        });

        return view('ranking.index', compact('rankedBooks'));
    }
}
