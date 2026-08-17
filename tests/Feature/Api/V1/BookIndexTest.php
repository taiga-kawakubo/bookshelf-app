<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーは書籍一覧を取得できる(): void
    {
        $bookOwner = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '公開一覧で取得する書籍',
        ]);

        $response = $this->getJson(route('api.v1.books.index'));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $book->id);
        $response->assertJsonPath('data.0.title', '公開一覧で取得する書籍');
    }

    public function test_書籍一覧は書籍情報をjson形式で返す(): void
    {
        $bookOwner = User::factory()->create();
        $firstReviewer = User::factory()->create();
        $secondReviewer = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'API一覧確認の書籍',
            'author' => '一覧 太郎',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-10',
            'image_url' => 'https://example.com/index-book.jpg',
        ]);

        $bookWithoutReview = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'レビューなしの書籍',
            'author' => '未評価 花子',
            'isbn' => '1234567890124',
            'published_date' => '2026-01-11',
            'image_url' => null,
        ]);

        $book->genres()->attach($genre->id);

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

        $response = $this->getJson(route('api.v1.books.index'));

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'user_id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'image_url',
                    'genres',
                    'average_rating',
                ],
            ],
            'links',
            'meta',
            'genres' => [
                [
                    'id',
                    'name',
                ],
            ],
        ]);

        $books = collect($response->json('data'))->keyBy('id');

        $bookData = $books->get($book->id);
        $bookWithoutReviewData = $books->get($bookWithoutReview->id);

        $this->assertCount(2, $books);

        $this->assertSame($book->id, $bookData['id']);
        $this->assertSame($bookOwner->id, $bookData['user_id']);
        $this->assertSame('API一覧確認の書籍', $bookData['title']);
        $this->assertSame('一覧 太郎', $bookData['author']);
        $this->assertSame('1234567890123', $bookData['isbn']);
        $this->assertSame('2026-01-10', $bookData['published_date']);
        $this->assertSame('https://example.com/index-book.jpg', $bookData['image_url']);
        $this->assertSame(4.5, $bookData['average_rating']);
        $this->assertSame([
            [
                'id' => $genre->id,
                'name' => '技術書',
            ],
        ], $bookData['genres']);

        $this->assertNull($bookWithoutReviewData['average_rating']);
    }

    public function test_書籍一覧はper_pageで指定した件数だけ返す(): void
    {
        $bookOwner = User::factory()->create();

        Book::factory()
            ->count(3)
            ->create([
                'user_id' => $bookOwner->id,
            ]);

        $response = $this->getJson(
            route('api.v1.books.index', [
                'per_page' => 2,
            ])
        );

        $response->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 3);
    }

    public function test_キーワードでタイトルまたは著者を検索できる(): void
    {
        $user = User::factory()->create();

        $titleMatchedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $authorMatchedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'PHPの本',
            'author' => 'Laravel太郎',
        ]);

        $notMatchedBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'JavaScript入門',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson(route('api.v1.books.index', [
            'keyword' => 'Laravel',
        ]));

        $response->assertOk();

        $bookIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [
                $titleMatchedBook->id,
                $authorMatchedBook->id,
            ],
            $bookIds
        );

        $this->assertNotContains($notMatchedBook->id, $bookIds);
    }

    public function test_ジャンルで書籍を絞り込める(): void
    {
        $user = User::factory()->create();

        $targetGenre = Genre::create([
            'name' => '技術書',
        ]);

        $otherGenre = Genre::create([
            'name' => '小説',
        ]);

        $targetBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $targetBook->genres()->attach($targetGenre->id);
        $otherBook->genres()->attach($otherGenre->id);

        $response = $this->getJson(route('api.v1.books.index', [
            'genre' => $targetGenre->id,
        ]));

        $response->assertOk();

        $bookIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$targetBook->id], $bookIds);
    }

    public function test_newestを指定すると新しい順に表示される(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson(route('api.v1.books.index', [
            'sort' => 'newest',
        ]));

        $response->assertOk();

        $titles = collect($response->json('data'))
            ->pluck('title')
            ->all();

        $this->assertSame(
            ['新しい書籍', '古い書籍'],
            $titles
        );
    }

    public function test_oldestを指定すると古い順に表示される(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson(route('api.v1.books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertOk();

        $titles = collect($response->json('data'))
            ->pluck('title')
            ->all();

        $this->assertSame(
            ['古い書籍', '新しい書籍'],
            $titles
        );
    }

    public function test_titleを指定するとタイトル順に表示される(): void
    {
        Book::factory()->create([
            'title' => 'C Book',
        ]);

        Book::factory()->create([
            'title' => 'A Book',
        ]);

        Book::factory()->create([
            'title' => 'B Book',
        ]);

        $response = $this->getJson(route('api.v1.books.index', [
            'sort' => 'title',
        ]));

        $response->assertOk();

        $titles = collect($response->json('data'))
            ->pluck('title')
            ->all();

        $this->assertSame(
            ['A Book', 'B Book', 'C Book'],
            $titles
        );
    }

    public function test_ratingを指定すると評価順に表示されレビューのない書籍は最後になる(): void
    {
        $reviewer = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'title' => '評価5の書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '評価3の書籍',
        ]);

        $notReviewedBook = Book::factory()->create([
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

        $response = $this->getJson(route('api.v1.books.index', [
            'sort' => 'rating',
        ]));

        $response->assertOk();
        $response->assertJsonCount(3, 'data');

        $titles = collect($response->json('data'))
            ->pluck('title')
            ->all();

        $this->assertSame(
            ['評価5の書籍', '評価3の書籍', 'レビューなしの書籍'], $titles
        );
    }

    public function test_書籍一覧は不正なページ指定の場合バリデーションエラーを返す(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'page' => 0,
                'per_page' => 101,
            ])
        );

        $response->assertStatus(422);

        $response->assertJsonPath(
            'message',
            '入力内容に誤りがあります。'
        );

        $response->assertJsonValidationErrors([
            'page',
            'per_page',
        ]);

        $response->assertJsonPath(
            'errors.page.0',
            'ページ番号は1以上で指定してください。'
        );

        $response->assertJsonPath(
            'errors.per_page.0',
            'ページあたりの件数は100以下で指定してください。'
        );
    }

    public function test_存在しないジャンルを指定した場合は422を返す(): void
    {
        $response = $this->getJson(route('api.v1.books.index', [
            'genre' => 999999,
        ]));

        $response->assertStatus(422);

        $response->assertJsonPath(
            'message',
            '入力内容に誤りがあります。'
        );

        $response->assertJsonValidationErrors('genre');
    }

    public function test_書籍一覧は書籍がない場合に空配列を返す(): void
    {
        $response = $this->getJson(route('api.v1.books.index'));

        $response->assertOk();

        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }
}
