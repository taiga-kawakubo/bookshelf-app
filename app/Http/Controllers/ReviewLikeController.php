<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        $result = $request
            ->user()
            ->likedReviews()
            ->toggle($review->id);

        $message = count($result['attached']) > 0
            ? 'レビューにいいねしました。'
            : 'レビューのいいねを解除しました。';

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', $message);
    }
}
