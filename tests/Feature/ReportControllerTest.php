<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証ユーザーがレポート画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');
    }

    public function test_自分の読書データだけが集計される(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $targetBook = Book::factory()->create([
            'user_id' => $targetUser->id,
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        ReadingPlan::create([
            'user_id' => $targetUser->id,
            'book_id' => $targetBook->id,
            'target_date' => today(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'target_date' => today(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $targetBook->reviews()->create([
            'user_id' => $targetUser->id,
            'rating' => 5,
            'comment' => '自分のレビュー',
        ]);

        $otherBook->reviews()->create([
            'user_id' => $otherUser->id,
            'rating' => 1,
            'comment' => '他ユーザーのレビュー',
        ]);
        $response = $this
            ->actingAs($targetUser)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');

        $stats = $response->viewData('stats');

        $this->assertSame(
            1,
            $stats['summary']['total_reviews']
        );

        $this->assertSame(
            1,
            $stats['summary']['books_read']
        );

        $this->assertSame(
            5.0,
            $stats['summary']['average_rating']
        );
    }

    public function test_高評価書籍が評価順に表示される(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '評価5の書籍',
        ]);

        $secondRatedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '評価4の書籍',
        ]);

        $highRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '高評価レビュー',
        ]);

        $secondRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '評価4のレビュー',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');

        $topRatedBooks = $response
            ->viewData('stats')['top_rated_books'];

        $this->assertSame(
            [$highRatedBook->id, $secondRatedBook->id],
            $topRatedBooks->pluck('id')->values()->all()
        );

        $this->assertSame(
            [5.0, 4.0],
            $topRatedBooks->pluck('rating')->values()->all()
        );
    }

    public function test_ジャンル別にレビュー評価が集計される(): void
    {
        $bookOwnerUser = User::factory()->create();
        $reviewUser = User::factory()->create();

        $genre1 = Genre::create([
            'name' => 'プログラミング',
        ]);

        $genre2 = Genre::create([
            'name' => '小説',
        ]);

        $book1 = Book::factory()->create([
            'user_id' => $bookOwnerUser->id,
            'title' => 'プログラミングの本',
        ]);

        $book2 = Book::factory()->create([
            'user_id' => $bookOwnerUser->id,
            'title' => '小説の本',
        ]);

        $book1->genres()->attach($genre1->id);
        $book2->genres()->attach($genre2->id);

        $book1->reviews()->create([
            'user_id' => $reviewUser->id,
            'rating' => 5,
            'comment' => 'レビュー',
        ]);

        $book2->reviews()->create([
            'user_id' => $reviewUser->id,
            'rating' => 3,
            'comment' => 'レビュー',
        ]);

        $response = $this
            ->actingAs($reviewUser)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');

        $genreRatings = $response
            ->viewData('stats')['genre_ratings'];

        $this->assertCount(2, $genreRatings);

        $programmingRating = $genreRatings
            ->firstWhere('id', $genre1->id);

        $novelRating = $genreRatings
            ->firstWhere('id', $genre2->id);

        $this->assertSame('プログラミング', $programmingRating['name']);
        $this->assertSame(5.0, $programmingRating['average_rating']);

        $this->assertSame('小説', $novelRating['name']);
        $this->assertSame(3.0, $novelRating['average_rating']);
    }



    public function test_読書データがない場合も画面が表示される(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');

        $stats = $response->viewData('stats');

        $this->assertSame(0, $stats['summary']['total_reviews']);
        $this->assertSame(0, $stats['summary']['books_read']);
        $this->assertSame(0.0, $stats['summary']['average_rating']);
        $this->assertCount(0, $stats['top_rated_books']);
        $this->assertCount(0, $stats['genre_ratings']);
    }

    public function test_高評価書籍の表示に評価が4未満の書籍が除外される(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '表示される書籍',
        ]);

        $secondRatedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '表示されない書籍',
        ]);

        $highRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '評価5のレビュー',
        ]);

        $secondRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '評価3のレビュー',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');

        $topRatedBooks = $response
            ->viewData('stats')['top_rated_books'];

        $this->assertCount(1, $topRatedBooks);

        $this->assertSame(
            [$highRatedBook->id],
            $topRatedBooks->pluck('id')->values()->all()
        );

        $this->assertNotContains(
            $secondRatedBook->id,
            $topRatedBooks->pluck('id')->all()
        );

        $response->assertDontSeeText('表示されない書籍');
    }

    public function test_高評価書籍の表示は6册以上ある場合は上位5册までが表示される(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();

        $books = Book::factory()
            ->count(6)
            ->create([
                'user_id' => $user->id,
            ]);

        $ratings = collect([5, 5, 5, 5, 5, 4]);

        $books->each(function (Book $book, int $index) use ($reviewer, $ratings): void {
            $book->reviews()->create([
                'user_id' => $reviewer->id,
                'rating' => $ratings->get($index),
                'comment' => 'TOP5確認用のレビュー',
            ]);
        });

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');

        $topRatedBooks = $response
            ->viewData('stats')['top_rated_books'];

        $this->assertCount(5, $topRatedBooks);
        $this->assertSame(
            [5.0, 5.0, 5.0, 5.0, 5.0],
            $topRatedBooks->pluck('rating')->all()
        );
        $this->assertNotContains(
            $books->last()->id,
            $topRatedBooks->pluck('id')->all()
        );
        $response->assertDontSeeText($books->last()->title);
    }

    public function test_ジャンル別評価傾向TOP5は平均評価件数ジャンル名の順で表示される(): void
    {
        $bookOwnerUser = User::factory()->create();
        $reviewUser = User::factory()->create();

        $highestRatedGenre = Genre::create([
            'name' => '平均5ジャンル',
        ]);

        $moreReviewedGenre = Genre::create([
            'name' => '平均4件数多いジャンル',
        ]);

        $lessReviewedGenre = Genre::create([
            'name' => '平均4件数少ないジャンル',
        ]);

        $alphaGenre = Genre::create([
            'name' => 'Alpha',
        ]);

        $betaGenre = Genre::create([
            'name' => 'Beta',
        ]);

        $excludedGenre = Genre::create([
            'name' => '除外ジャンル',
        ]);

        $createReviewedBook = function (Genre $genre, int $rating, string $title) use ($bookOwnerUser, $reviewUser): void
        {
            $book = Book::factory()->create([
                'user_id' => $bookOwnerUser->id,
                'title' => $title,
            ]);

            $book->genres()->attach($genre->id);

            $book->reviews()->create([
                'user_id' => $reviewUser->id,
                'rating' => $rating,
                'comment' => 'ジャンル並び順確認用レビュー',
            ]);
        };

        $createReviewedBook($highestRatedGenre, 5, '平均5の本');

        $createReviewedBook($moreReviewedGenre, 4, '平均4件数多い本1');
        $createReviewedBook($moreReviewedGenre, 4, '平均4件数多い本2');

        $createReviewedBook($lessReviewedGenre, 4, '平均4件数少ない本');

        $createReviewedBook($alphaGenre, 3, 'Alphaの本');
        $createReviewedBook($betaGenre, 3, 'Betaの本');

        $createReviewedBook($excludedGenre, 2, '除外される本');

        $response = $this
            ->actingAs($reviewUser)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');

        $genreRatings = $response
            ->viewData('stats')['genre_ratings'];

        $this->assertCount(5, $genreRatings);

        $this->assertSame(
            [
                $highestRatedGenre->id,
                $moreReviewedGenre->id,
                $lessReviewedGenre->id,
                $alphaGenre->id,
                $betaGenre->id,
            ],
            $genreRatings->pluck('id')->values()->all()
        );

        $this->assertNotContains(
            $excludedGenre->id,
            $genreRatings->pluck('id')->all()
        );
    }


    public function test_レビューがない書籍は除外される(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();

        $reviewedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '表示される書籍',
        ]);

        $notReviewedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '表示されない書籍',
        ]);

        $reviewedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'レビュー',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $reviewedBooks = $response
            ->viewData('stats')['top_rated_books'];

        $this->assertCount(1, $reviewedBooks);

        $this->assertSame(
            [$reviewedBook->id],
            $reviewedBooks->pluck('id')->values()->all()
        );

        $this->assertNotContains(
            $notReviewedBook->id,
            $reviewedBooks->pluck('id')->all()
        );

        $response->assertDontSeeText('表示されない書籍');
    }

    public function test_未認証ユーザーはアクセスできない(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }
    
}
