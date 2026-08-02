<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍詳細_ap_iは指定した書籍情報を_jso_nで返す(): void
    {
        $bookOwner = User::factory()->create([
            'name' => '書籍登録者',
        ]);

        $reviewer = User::factory()->create([
            'name' => 'レビュー投稿者',
        ]);

        $genre = Genre::create([
            'name' => '実用書',
        ]);

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'API詳細確認の書籍',
            'author' => '詳細 太郎',
            'isbn' => '2234567890123',
            'published_date' => '2026-02-10',
            'description' => 'API詳細で返す説明文です。',
            'image_url' => 'https://example.com/show-book.jpg',
        ]);

        $book->genres()->attach($genre->id);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '詳細APIで返すレビューです。',
        ]);

        $response = $this->getJson(route('api.v1.books.show', $book));

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'title',
                'author',
                'isbn',
                'published_date',
                'image_url',
                'description',
                'genres' => [
                    [
                        'id',
                        'name',
                    ],
                ],
                'reviews' => [
                    [
                        'id',
                        'rating',
                        'comment',
                        'user' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.user_id', $bookOwner->id);
        $response->assertJsonPath('data.title', 'API詳細確認の書籍');
        $response->assertJsonPath('data.author', '詳細 太郎');
        $response->assertJsonPath('data.isbn', '2234567890123');
        $response->assertJsonPath('data.published_date', '2026-02-10');
        $response->assertJsonPath('data.description', 'API詳細で返す説明文です。');
        $response->assertJsonPath('data.image_url', 'https://example.com/show-book.jpg');

        $response->assertJsonPath('data.genres.0.id', $genre->id);
        $response->assertJsonPath('data.genres.0.name', '実用書');

        $response->assertJsonPath('data.reviews.0.id', $review->id);
        $response->assertJsonPath('data.reviews.0.rating', 5);
        $response->assertJsonPath('data.reviews.0.comment', '詳細APIで返すレビューです。');
        $response->assertJsonPath('data.reviews.0.user.id', $reviewer->id);
        $response->assertJsonPath('data.reviews.0.user.name', 'レビュー投稿者');
    }

    public function test_書籍詳細_ap_iは指定した書籍だけを返す(): void
    {
        $bookOwner = User::factory()->create();

        $targetBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '取得対象の書籍',
            'isbn' => '3234567890123',
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '取得対象ではない書籍',
            'isbn' => '3234567890124',
        ]);

        $response = $this->getJson(route('api.v1.books.show', $targetBook));

        $response->assertOk();

        $response->assertJsonPath('data.id', $targetBook->id);
        $response->assertJsonPath('data.title', '取得対象の書籍');

        $response->assertJsonMissing([
            'id' => $otherBook->id,
            'title' => '取得対象ではない書籍',
        ]);
    }

    public function test_レビューがない書籍詳細_ap_iはreviewsを空配列で返す(): void
    {
        $bookOwner = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => 'レビューがない書籍',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->getJson(route('api.v1.books.show', $book));

        $response->assertOk();

        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.genres.0.id', $genre->id);
        $response->assertJsonPath('data.genres.0.name', '技術書');
        $response->assertJsonPath('data.reviews', []);
    }

    public function test_存在しない書籍詳細_ap_iは404を返す(): void
    {
        $response = $this->getJson(route('api.v1.books.show', 999999));

        $response->assertNotFound();

        $response->assertJsonStructure([
            'message',
        ]);

        $response->assertJsonPath(
            'message',
            '対象の書籍が見つかりませんでした。'
        );
    }

    public function test_不正な形式の書籍_i_dを指定した場合は404を返す(): void
    {
        $response = $this->getJson(route('api.v1.books.show', 'invalid-id'));

        $response->assertNotFound();

        $response->assertJsonStructure([
            'message',
        ]);
    }
}
