<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 検証に必要なジャンルを作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);
    }

    public function test_書籍一覧画面に書籍情報と紐づく複数ジャンルが表示される(): void
    {
        $genres = Genre::query()
            ->take(2)
            ->get();

        $this->assertCount(2, $genres);

        $book = Book::factory()->create([
            'title' => 'Laravelテスト入門',
            'author' => '山田太郎',
        ]);

        $book->genres()->attach(
            $genres->pluck('id')->all()
        );

        $response = $this->get(route('books.index'));

        $response->assertOk();

        $response->assertSeeText($book->title);
        $response->assertSeeText($book->author);

        $genres
            ->each(function (Genre $genre) use ($response): void {
                $response->assertSeeText($genre->name);
            });
    }

    public function test_キーワードがタイトルに一致する書籍を表示する(): void
    {
        $targetBook = Book::factory()->create([
            'title' => 'テスト書籍１',
            'author' => '山田太郎',
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'テスト書籍２',
            'author' => '佐藤花子',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => 'テスト書籍１',
        ]));

        $response->assertOk();
        $response->assertSeeText($targetBook->title);
        $response->assertDontSeeText($otherBook->title);
    }

    public function test_キーワードが著者名に一致する書籍を表示する(): void
    {
        $targetBook = Book::factory()->create([
            'title' => 'テスト書籍１',
            'author' => '山田太郎',
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'テスト書籍２',
            'author' => '佐藤花子',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '山田太郎',
        ]));

        $response->assertOk();
        $response->assertSeeText($targetBook->title);
        $response->assertDontSeeText($otherBook->title);
    }

    public function test_ジャンルで絞り込める(): void
    {
        $targetGenre = Genre::create([
            'name' => '対象ジャンル',
        ]);

        $otherGenre = Genre::create([
            'name' => '対象外ジャンル',
        ]);

        $targetBook = Book::factory()->create([
            'title' => '対象ジャンルの書籍',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '対象外ジャンルの書籍',
        ]);

        $targetBook->genres()->attach($targetGenre->id);
        $otherBook->genres()->attach($otherGenre->id);

        $response = $this->get(route('books.index', [
            'genre' => $targetGenre->id,
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertCount(1, $books);
        $this->assertSame($targetBook->id, $books->first()->id);
        $response->assertSeeText('対象ジャンルの書籍');
        $response->assertDontSeeText('対象外ジャンルの書籍');
    }

    public function test_newestを指定すると登録日が新しい順に表示される(): void
    {
        $oldBook = Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'newest',
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertSame(
            [$newBook->id, $oldBook->id],
            $books->pluck('id')->values()->all()
        );
    }

    public function test_oldestを指定すると登録日が古い順に表示される(): void
    {
        $oldBook = Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertSame(
            [$oldBook->id, $newBook->id],
            $books->pluck('id')->values()->all()
        );
    }

    public function test_titleを指定するとタイトル昇順に表示される(): void
    {
        $bookC = Book::factory()->create([
            'title' => 'C Book',
        ]);

        $bookA = Book::factory()->create([
            'title' => 'A Book',
        ]);

        $bookB = Book::factory()->create([
            'title' => 'B Book',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'title',
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertSame(
            [$bookA->id, $bookB->id, $bookC->id],
            $books->pluck('id')->values()->all()
        );
    }

    public function test_ratingを指定すると評価が高い順に表示される(): void
    {
        $reviewer = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'title' => '評価5の書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '評価3の書籍',
        ]);

        $highRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '評価5のレビュー',
        ]);

        $lowRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '評価3のレビュー',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertSame(
            [$highRatedBook->id, $lowRatedBook->id],
            $books->pluck('id')->values()->all()
        );
    }

    public function test_存在しないジャンルを指定した場合はバリデーションエラーになる(): void
    {
        Book::factory()->create([
            'title' => '表示されない書籍',
        ]);

        $missingGenreId = (Genre::query()->max('id') ?? 0) + 1;

        $response = $this
            ->from(route('books.index'))
            ->get(route('books.index', [
                'genre' => $missingGenreId,
            ]));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHasErrors('genre');
    }

    public function test_一致しないキーワードでは書籍が表示されない(): void
    {
        Book::factory()->create([
            'title' => 'テスト書籍１',
            'author' => '山田太郎',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '存在しないキーワード',
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $books
        );
        $this->assertCount(0, $books);
        $this->assertSame(0, $books->total());
    }

    public function test_rating順ではレビューがない書籍は最後に表示される(): void
    {
        $reviewer = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'title' => '評価5の書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '評価3の書籍',
        ]);

        $noReviewBook = Book::factory()->create([
            'title' => 'レビューなしの書籍',
        ]);

        $highRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '評価5のレビュー',
        ]);

        $lowRatedBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '評価3のレビュー',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertSame(
            [$highRatedBook->id, $lowRatedBook->id, $noReviewBook->id],
            $books->pluck('id')->values()->all()
        );
    }

    public function test_書籍が10件の場合は1ページ目に10件すべて表示される(): void
    {
        Book::factory()
            ->count(10)
            ->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $books
        );

        $this->assertCount(10, $books);
        $this->assertSame(10, $books->total());
        $this->assertSame(10, $books->perPage());
        $this->assertSame(1, $books->currentPage());
        $this->assertSame(1, $books->lastPage());
    }

    public function test_書籍が11件の場合は10件ごとにページネーションされる(): void
    {
        Book::factory()
            ->count(11)
            ->create();

        $firstPageResponse = $this->get(
            route('books.index')
        );

        $firstPageResponse->assertOk();

        $firstPageBooks = $firstPageResponse->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $firstPageBooks
        );

        $this->assertCount(10, $firstPageBooks);
        $this->assertSame(11, $firstPageBooks->total());
        $this->assertSame(10, $firstPageBooks->perPage());
        $this->assertSame(1, $firstPageBooks->currentPage());
        $this->assertSame(2, $firstPageBooks->lastPage());

        $secondPageResponse = $this->get(
            route('books.index', ['page' => 2])
        );

        $secondPageResponse->assertOk();

        $secondPageBooks = $secondPageResponse->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $secondPageBooks
        );

        $this->assertCount(1, $secondPageBooks);
        $this->assertSame(11, $secondPageBooks->total());
        $this->assertSame(10, $secondPageBooks->perPage());
        $this->assertSame(2, $secondPageBooks->currentPage());
        $this->assertSame(2, $secondPageBooks->lastPage());
    }
}
