<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * ログインユーザーの読書データを集計し、マイ読書レポート画面を表示する。
     *
     * @return View マイ読書レポート画面
     */
    public function index(): View
    {
        // 基本サマリー
        $user = auth()->user();

        $user->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        $booksRead = $user->readingPlans()
            ->where('status', ReadingPlanStatus::Completed)
            ->pluck('book_id')
            ->unique()
            ->count();

        // 評価分布
        $ratingDistribution = collect([
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
        ]);

        $user->reviews->each(function ($review) use (&$ratingDistribution) {
            $ratingDistribution->put(
                $review->rating,
                $ratingDistribution->get($review->rating) + 1
            );
        });

        // 高評価書籍TOP5
        $previousAverageRating = null;
        $currentRank = 0;

        $topRatedBooks = $user->books()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('updated_at')
            ->orderBy('title')
            ->get()
            ->filter(function ($book) {
                $averageRating = (float) ($book->reviews_avg_rating ?? 0);

                return $averageRating >= 4.0;
            })
            ->take(5)
            ->values()
            ->map(function ($book, int $index) use (&$previousAverageRating, &$currentRank) {
                $averageRating = (float) $book->reviews_avg_rating;

                if ($averageRating !== $previousAverageRating) {
                    $currentRank++;
                }

                $previousAverageRating = $averageRating;

                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'rating' => round($averageRating),
                    'rank' => $currentRank,
                ];
            });

        // ジャンル別評価傾向TOP５
        $previousAverageRating = null;
        $currentRank = 0;

        $reviews = $user->reviews()
            ->with('book.genres')
            ->get();

        $genreRatings = [];

        $reviews->each(function ($review) use (&$genreRatings) {

            $review->book->genres->each(function ($genre) use ($review, &$genreRatings) {
                if (! isset($genreRatings[$genre->id])) {
                    $genreRatings[$genre->id] = [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'ratings' => [],
                    ];
                }

                $genreRatings[$genre->id]['ratings'][] = $review->rating;
            });
        });

        $genreRatings = collect($genreRatings)
            ->map(function ($genre) {
                return [
                    'id' => $genre['id'],
                    'name' => $genre['name'],
                    'count' => count($genre['ratings']),
                    'average_rating' => collect($genre['ratings'])->avg(),
                ];
            });

        $genreRatings = $genreRatings
            ->sort(function ($a, $b) {
                if ($a['average_rating'] !== $b['average_rating']) {
                    return $b['average_rating'] <=> $a['average_rating'];
                }

                if ($a['count'] !== $b['count']) {
                    return $b['count'] <=> $a['count'];
                }

                return strcmp($a['name'], $b['name']);
            })
            ->take(5)
            ->values()
            ->map(function ($genre, int $index) use (&$previousAverageRating, &$currentRank) {
                $averageRating = (float) $genre['average_rating'];

                if ($averageRating !== $previousAverageRating) {
                    $currentRank++;
                }

                $previousAverageRating = $averageRating;

                return [
                    'id' => $genre['id'],
                    'name' => $genre['name'],
                    'count' => $genre['count'],
                    'average_rating' => round($averageRating, 1),
                    'rank' => $currentRank,
                ];
            });

        $stats = [
            'summary' => [
                'total_reviews' => $user->reviews_count,
                'books_read' => $booksRead,
                'average_rating' => round($user->reviews_avg_rating ?? 0, 1),
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
