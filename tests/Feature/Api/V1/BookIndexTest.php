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

    public function test_書籍一覧APIは書籍情報をJSONで返す(): void
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
                    'genres' => [
                        [
                            'id',
                            'name',
                        ],
                    ],
                    'average_rating',
                ],
            ],
            'links',
            'meta',
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

    public function test_書籍一覧APIは書籍がない場合に空配列を返す(): void
    {
        $response = $this->getJson(route('api.v1.books.index'));

        $response->assertOk();

        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_書籍一覧APIはper_pageで指定した件数だけ返す(): void
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

    public function test_書籍一覧APIは不正なページ指定の場合バリデーションエラーを返す(): void
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
}
