<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()
            ->pluck('id');

        $reviews = Review::query()
            ->orderBy('id')
            ->get();

        foreach ($reviews as $index => $review) {
            $likeCount = $index % 4;

            $likerIds = $userIds
                ->reject(function ($userId) use ($review) {
                    return (int) $userId === (int) $review->user_id;
                })
                ->values()
                ->take($likeCount)
                ->all();

            $review->likedByUsers()
                ->syncWithoutDetaching($likerIds);
        }
    }
}
