<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーはランキング画面を表示できる(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertViewIs('ranking.index');
        $response->assertViewHas('rankedBooks');

        $this->assertGuest();
    }

    public function test_認証済みユーザーもランキング画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('ranking.index'));

        $response->assertOk();
        $response->assertViewIs('ranking.index');
        $response->assertViewHas('rankedBooks');
    }

    public function test_ランキング画面に平均評価とレビュー件数と書籍詳細リンクが表示される(): void
    {
        $bookOwner = User::factory()->create();
        $firstReviewer = User::factory()->create();
        $secondReviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '表示内容確認用の書籍',
        ]);

        $book->reviews()->create([
            'user_id' => $firstReviewer->id,
            'rating' => 5,
            'comment' => '評価5のレビューです。',
        ]);

        $book->reviews()->create([
            'user_id' => $secondReviewer->id,
            'rating' => 4,
            'comment' => '評価4のレビューです。',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeText($book->title);
        $response->assertSeeText('4.50');
        $response->assertSeeText('2件のレビュー');

        $response->assertSee(
            'href="'.route('books.show', $book).'"',
            false
        );

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($book): bool {
            $bookFromView = $rankedBooks->first();

            return $rankedBooks->count() === 1
                && $bookFromView->is($book)
                && (float) $bookFromView->reviews_avg_rating === 4.5
                && (int) $bookFromView->reviews_count === 2;
        });
    }

    public function test_レビュー平均評価が高い順に表示される(): void
    {
        $bookOwner = User::factory()->create();

        $highReviewer = User::factory()->create();
        $middleReviewer = User::factory()->create();
        $lowReviewer = User::factory()->create();

        $lowBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '低評価の書籍',
        ]);

        $highBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '高評価の書籍',
        ]);

        $middleBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '中評価の書籍',
        ]);

        $lowBook->reviews()->create([
            'user_id' => $lowReviewer->id,
            'rating' => 3,
            'comment' => '評価3のレビューです。',
        ]);

        $highBook->reviews()->create([
            'user_id' => $highReviewer->id,
            'rating' => 5,
            'comment' => '評価5のレビューです。',
        ]);

        $middleBook->reviews()->create([
            'user_id' => $middleReviewer->id,
            'rating' => 4,
            'comment' => '評価4のレビューです。',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($highBook, $middleBook, $lowBook): bool {
            return $rankedBooks->pluck('id')->all() === [
                $highBook->id,
                $middleBook->id,
                $lowBook->id,
            ];
        });

        $response->assertSeeTextInOrder([
            $highBook->title,
            $middleBook->title,
            $lowBook->title,
        ]);
    }

    public function test_同じ平均評価の書籍は同順位となる(): void
    {
        $bookOwner = User::factory()->create();
        $reviewers = User::factory()->count(4)->create();

        $firstPlaceBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '1位の書籍',
        ]);

        $firstTieBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '同順位2位の書籍A',
        ]);

        $secondTieBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '同順位2位の書籍B',
        ]);

        $thirdPlaceBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '3位の書籍',
        ]);

        $firstPlaceBook->reviews()->create([
            'user_id' => $reviewers[0]->id,
            'rating' => 5,
            'comment' => '評価5のレビューです。',
        ]);

        $firstTieBook->reviews()->create([
            'user_id' => $reviewers[1]->id,
            'rating' => 4,
            'comment' => '評価4のレビューです。',
        ]);

        $secondTieBook->reviews()->create([
            'user_id' => $reviewers[2]->id,
            'rating' => 4,
            'comment' => '評価4のレビューです。',
        ]);

        $thirdPlaceBook->reviews()->create([
            'user_id' => $reviewers[3]->id,
            'rating' => 3,
            'comment' => '評価3のレビューです。',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($firstPlaceBook, $firstTieBook, $secondTieBook, $thirdPlaceBook): bool {
            $booksById = $rankedBooks->keyBy('id');

            return (int) $booksById->get($firstPlaceBook->id)->rank === 1
                && (int) $booksById->get($firstTieBook->id)->rank === 2
                && (int) $booksById->get($secondTieBook->id)->rank === 2
                && (int) $booksById->get($thirdPlaceBook->id)->rank === 3;
        });
    }

    public function test_同じ平均評価の中ではレビュー数が多い書籍が先に表示される(): void
    {
        $bookOwner = User::factory()->create();
        $reviewers = User::factory()->count(5)->create();

        $manyReviewsBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'レビュー数が多い書籍',
        ]);

        $fewReviewsBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'レビュー数が少ない書籍',
        ]);

        $manyReviewsBook->reviews()->create([
            'user_id' => $reviewers[0]->id,
            'rating' => 4,
            'comment' => '1件目の評価4レビューです。',
        ]);

        $manyReviewsBook->reviews()->create([
            'user_id' => $reviewers[1]->id,
            'rating' => 4,
            'comment' => '2件目の評価4レビューです。',
        ]);

        $manyReviewsBook->reviews()->create([
            'user_id' => $reviewers[2]->id,
            'rating' => 4,
            'comment' => '3件目の評価4レビューです。',
        ]);

        $fewReviewsBook->reviews()->create([
            'user_id' => $reviewers[3]->id,
            'rating' => 4,
            'comment' => '1件目の評価4レビューです。',
        ]);

        $fewReviewsBook->reviews()->create([
            'user_id' => $reviewers[4]->id,
            'rating' => 4,
            'comment' => '2件目の評価4レビューです。',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($manyReviewsBook, $fewReviewsBook): bool {
            return $rankedBooks->pluck('id')->all() === [
                $manyReviewsBook->id,
                $fewReviewsBook->id,
            ];
        });

        $response->assertSeeTextInOrder([
            $manyReviewsBook->title,
            $fewReviewsBook->title,
        ]);
    }

    public function test_平均評価とレビュー数が同じ場合は更新日時が新しい書籍が先に表示される(): void
    {
        $bookOwner = User::factory()->create();
        $reviewers = User::factory()->count(2)->create();

        /*
         * タイトル順だけなら古い書籍が先になる名前にしておき、updated_atの並び順が優先されることを確認する。
         */
        $oldBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'Alpha Old Book',
        ]);

        $newBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'Zulu New Book',
        ]);

        $oldBook->reviews()->create([
            'user_id' => $reviewers[0]->id,
            'rating' => 4,
            'comment' => '古い書籍のレビューです。',
        ]);

        $newBook->reviews()->create([
            'user_id' => $reviewers[1]->id,
            'rating' => 4,
            'comment' => '新しい書籍のレビューです。',
        ]);

        Book::query()
            ->whereKey($oldBook->id)
            ->update([
                'updated_at' => now()->subDay(),
            ]);

        Book::query()
            ->whereKey($newBook->id)
            ->update([
                'updated_at' => now(),
            ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($newBook, $oldBook): bool {
            $bookIds = $rankedBooks->pluck('id')->all();

            $ranks = $rankedBooks
                ->map(fn (Book $book): int => (int) $book->rank)
                ->all();

            return $bookIds === [$newBook->id, $oldBook->id]
                && $ranks === [1, 1];
        });
    }

    public function test_レビューがない書籍はランキングに表示されない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $reviewedBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'レビューがある書籍',
        ]);

        $bookWithoutReview = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'レビューがない書籍',
        ]);

        $reviewedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'ランキング対象のレビューです。',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeText($reviewedBook->title);
        $response->assertDontSeeText($bookWithoutReview->title);

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($reviewedBook, $bookWithoutReview): bool {
            return $rankedBooks->count() === 1
                && $rankedBooks->contains(
                    fn (Book $book): bool => $book->is($reviewedBook)
                )
                && ! $rankedBooks->contains(
                    fn (Book $book): bool => $book->is($bookWithoutReview)
                );
        });
    }

    public function test_レビュー付き書籍が11冊ある場合は上位10冊だけ表示される(): void
    {
        $bookOwner = User::factory()->create();
        $reviewers = User::factory()->count(11)->create();

        $topBooks = collect();

        for ($number = 1; $number <= 10; $number++) {
            $book = Book::factory()->create([
                'user_id' => $bookOwner->id,
                'title' => sprintf('上位書籍%02d', $number),
            ]);

            $book->reviews()->create([
                'user_id' => $reviewers[$number - 1]->id,
                'rating' => 5,
                'comment' => sprintf('上位書籍%02dのレビューです。', $number),
            ]);

            $topBooks->push($book);
        }

        $eleventhBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '11位になる書籍',
        ]);

        $eleventhBook->reviews()->create([
            'user_id' => $reviewers[10]->id,
            'rating' => 1,
            'comment' => '11位になる書籍のレビューです。',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertDontSeeText($eleventhBook->title);

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($topBooks, $eleventhBook): bool {
            $expectedBookIds = $topBooks
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            $actualBookIds = $rankedBooks
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            return $rankedBooks->count() === 10
                && $actualBookIds === $expectedBookIds
                && ! $rankedBooks->contains(fn (Book $book): bool => $book->is($eleventhBook));
        });
    }

    public function test_ランキング対象書籍がない場合は空状態メッセージが表示される(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertSeeText(
            'まだレビューが投稿された書籍がありません。'
        );

        $response->assertViewHas('rankedBooks', fn ($rankedBooks): bool => $rankedBooks->isEmpty()
        );
    }
}
