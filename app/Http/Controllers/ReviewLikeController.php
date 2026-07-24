<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\Review;

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
