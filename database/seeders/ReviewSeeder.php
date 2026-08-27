<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();
        $faker->seed(20260816);

        $commentsByRating = [
            1 => '自分にはあまり合わず、読み進めるのが難しく感じました。',
            2 => '参考になる部分はありましたが、少し物足りなさも感じました。',
            3 => '全体的に読みやすく、いくつか学びがありました。',
            4 => '内容が分かりやすく、実生活にも活かせそうだと感じました。',
            5 => 'とても満足度が高く、強くおすすめしたい一冊です。',
        ];

        $bookIsbns = [
            '9784101010014',
            '9784422100524',
            '9784873115658',
            '9784863940246',
            '9784101010021',
            '9784309226712',
            '9784048930598',
            '9784478025819',
            '9784163902302',
            '9784822289607',
            '9784822251468',
        ];

        $reviewerEmails = [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ];

        // 各書籍2〜4件
        $reviewCountByIsbn = [
            '9784101010014' => 2,
            '9784422100524' => 3,
            '9784873115658' => 4,
            '9784863940246' => 2,
            '9784101010021' => 3,
            '9784309226712' => 4,
            '9784048930598' => 2,
            '9784478025819' => 3,
            '9784163902302' => 4,
            '9784822289607' => 2,
            '9784822251468' => 3,
        ];

        $books = Book::query()
            ->whereIn('isbn', $bookIsbns)
            ->orderBy('isbn')
            ->get();

        $users = User::query()
            ->whereIn('email', $reviewerEmails)
            ->orderBy('email')
            ->get();

        $ratingCycle = [1, 2, 3, 4, 5];
        $ratingIndex = 0;

        $books->each(function (Book $book) use (
            $faker,
            $users,
            $commentsByRating,
            $reviewCountByIsbn,
            $ratingCycle,
            &$ratingIndex
        ): void {
            $reviewCount = $reviewCountByIsbn[$book->isbn];

            $reviewers = $faker->randomElements(
                $users->all(),
                $reviewCount,
                false
            );

            collect($reviewers)->each(function (User $user) use (
                $book,
                $commentsByRating,
                $ratingCycle,
                &$ratingIndex
            ): void {
                $rating = $ratingCycle[
                    $ratingIndex % count($ratingCycle)
                ];

                $ratingIndex++;

                Review::create([
                    'book_id' => $book->id,
                    'user_id' => $user->id,
                    'rating' => $rating,
                    'comment' => $commentsByRating[$rating],
                ]);
            });
        });
    }
}
