<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $bookOwner;

    private Genre $firstGenre;

    private Genre $secondGenre;

    /**
     * 検証に必要なユーザーとジャンルを作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->bookOwner = User::factory()->create();

        $this->firstGenre = Genre::create([
            'name' => '技術書',
        ]);

        $this->secondGenre = Genre::create([
            'name' => '実用書',
        ]);
    }

    /**
     * 正常な書籍登録APIリクエストデータを作成する。
     * テストごとに変更したい値は、$overrideで上書きする。
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'user_id' => $this->bookOwner->id,
            'title' => 'API登録確認の書籍',
            'author' => '登録 太郎',
            'isbn' => '4234567890123',
            'published_date' => '2026-03-15',
            'description' => 'API登録で保存する説明文です。',
            'image_url' => 'https://example.com/store-book.jpg',
            'genres' => [$this->firstGenre->id],
        ], $override);
    }

    public function test_書籍登録_ap_iは有効な値で書籍とジャンルを登録する(): void
    {
        $payload = $this->validData([
            'genres' => [
                $this->firstGenre->id,
                $this->secondGenre->id,
            ],
        ]);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertCreated();
        $response->assertHeader('Content-Type', 'application/json');

        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'user_id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres' => [
                    [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);

        $bookId = $response->json('data.id');
        $genres = collect($response->json('data.genres'))->keyBy('id');

        $response->assertJsonPath('message', '書籍を登録しました。');
        $response->assertJsonPath('data.user_id', $this->bookOwner->id);
        $response->assertJsonPath('data.title', 'API登録確認の書籍');
        $response->assertJsonPath('data.author', '登録 太郎');
        $response->assertJsonPath('data.isbn', '4234567890123');
        $response->assertJsonPath('data.published_date', '2026-03-15');
        $response->assertJsonPath('data.description', 'API登録で保存する説明文です。');
        $response->assertJsonPath('data.image_url', 'https://example.com/store-book.jpg');

        $this->assertCount(2, $genres);
        $this->assertSame('技術書', $genres->get($this->firstGenre->id)['name']);
        $this->assertSame('実用書', $genres->get($this->secondGenre->id)['name']);

        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'user_id' => $this->bookOwner->id,
            'title' => 'API登録確認の書籍',
            'author' => '登録 太郎',
            'isbn' => '4234567890123',
            'description' => 'API登録で保存する説明文です。',
            'image_url' => 'https://example.com/store-book.jpg',
        ]);

        $book = Book::query()->findOrFail($bookId);

        $this->assertSame(
            $payload['published_date'],
            $book->published_date->toDateString()
        );

        $this->assertDatabaseCount('book_genre', 2);
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $this->firstGenre->id,
        ]);
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $this->secondGenre->id,
        ]);
    }

    public function test_書籍登録_ap_iは任意項目がnullでも登録できる(): void
    {
        $payload = $this->validData([
            'isbn' => '4234567890124',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertCreated();

        $bookId = $response->json('data.id');

        $response->assertJsonPath('data.description', null);
        $response->assertJsonPath('data.image_url', null);

        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'user_id' => $this->bookOwner->id,
            'isbn' => '4234567890124',
            'description' => null,
            'image_url' => null,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $this->firstGenre->id,
        ]);
    }

    public function test_書籍登録_ap_iは任意項目が未入力でも登録できる(): void
    {
        $payload = $this->validData([
            'isbn' => '4234567890125',
        ]);

        unset($payload['description'], $payload['image_url']);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertCreated();

        $bookId = $response->json('data.id');

        $response->assertJsonPath('data.description', null);
        $response->assertJsonPath('data.image_url', null);

        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'user_id' => $this->bookOwner->id,
            'isbn' => '4234567890125',
            'description' => null,
            'image_url' => null,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $this->firstGenre->id,
        ]);
    }

    public function test_書籍登録_ap_iはバリデーションエラー時に書籍とジャンル紐付けを保存しない(): void
    {
        $payload = $this->validData([
            'genres' => [],
        ]);

        unset($payload['title']);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'title',
            'genres',
        ]);
        $response->assertJsonPath('errors.title.0', 'タイトルを入力してください。');
        $response->assertJsonPath('errors.genres.0', 'ジャンルを1つ以上選択してください。');

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_存在しない登録者_i_dでは書籍登録できない(): void
    {
        $missingUserId = User::query()->max('id') + 1;

        $payload = $this->validData([
            'user_id' => $missingUserId,
            'isbn' => '4234567890128',
        ]);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'user_id',
        ]);
        $response->assertJsonPath('errors.user_id.0', '指定された登録者は存在しません。');

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_isb_nが13桁ではない場合は書籍登録できない(): void
    {
        $payload = $this->validData([
            'isbn' => '123456789012',
        ]);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'isbn',
        ]);
        $response->assertJsonPath('errors.isbn.0', 'ISBNは13桁で入力してください。');

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_登録済み_isb_nでは書籍登録できない(): void
    {
        $existingOwner = User::factory()->create();

        Book::factory()->create([
            'user_id' => $existingOwner->id,
            'title' => '既存の書籍',
            'isbn' => '4234567890126',
        ]);

        $payload = $this->validData([
            'title' => '重複ISBNの書籍',
            'isbn' => '4234567890126',
        ]);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'isbn',
        ]);
        $response->assertJsonPath('errors.isbn.0', '入力されたISBNはすでに使用されています。');

        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseMissing('books', [
            'title' => '重複ISBNの書籍',
            'isbn' => '4234567890126',
        ]);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_ジャンル未指定では書籍登録できない(): void
    {
        $payload = $this->validData([
            'isbn' => '4234567890129',
        ]);

        unset($payload['genres']);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'genres',
        ]);
        $response->assertJsonPath('errors.genres.0', 'ジャンルを1つ以上選択してください。');

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_存在しないジャンル_i_dでは書籍登録できない(): void
    {
        $payload = $this->validData([
            'isbn' => '4234567890127',
            'genres' => [999999],
        ]);

        $response = $this->postJson(route('api.v1.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'genres.0',
        ]);
        $this->assertSame(
            '選択されたジャンルは存在しません。',
            $response->json('errors')['genres.0'][0]
        );

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }
}
