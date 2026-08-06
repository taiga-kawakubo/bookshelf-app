<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get();
        $books = Book::query()->get();

        $commentsByRating = collect([
            1 => '自分にはあまり合わず、読み進めるのが難しく感じました。',
            2 => '参考になる部分はありましたが、少し物足りなさも感じました。',
            3 => '全体的に読みやすく、いくつか学びがありました。',
            4 => '内容が分かりやすく、実生活にも活かせそうだと感じました。',
            5 => 'とても満足度が高く、強くおすすめしたい一冊です。',
        ]);

        $books->each(function($book)use($users, $commentsByRating) {
            $reviewCount = random_int(2, 4);

            $users
                ->shuffle()
                ->take($reviewCount)
                ->each(function ($user)use($book, $commentsByRating){
                    $rating = random_int(1, 5);

                    Review::firstOrCreate(
                        [
                            'book_id' => $book->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'rating' => $rating,
                            'comment' => $commentsByRating->get($rating),
                        ]
                    );
                });
        });
    }
}