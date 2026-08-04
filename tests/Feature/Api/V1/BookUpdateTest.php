<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $bookOwner;

    private User $anotherUser;

    private Book $book;

    private Genre $oldGenre;

    private Genre $firstNewGenre;

    private Genre $secondNewGenre;

    /**
     * 検証に必要なユーザー、ジャンル、更新対象書籍を作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->bookOwner = User::factory()->create();
        $this->anotherUser = User::factory()->create();

        $this->oldGenre = Genre::create([
            'name' => '更新前ジャンル',
        ]);

        $this->firstNewGenre = Genre::create([
            'name' => '技術書',
        ]);

        $this->secondNewGenre = Genre::create([
            'name' => '実用書',
        ]);

        $this->book = Book::factory()->create([
            'user_id' => $this->bookOwner->id,
            'title' => 'API更新前の書籍',
            'author' => '更新前 太郎',
            'isbn' => '5234567890123',
            'published_date' => '2026-04-01',
            'description' => '更新前の説明文です。',
            'image_url' => 'https://example.com/before-book.jpg',
        ]);

        $this->book->genres()->attach($this->oldGenre->id);
    }

    /**
     * 正常な書籍更新APIリクエストデータを作成する。
     * テストごとに変更したい値は、$overrideで上書きする。
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'title' => 'API更新後の書籍',
            'author' => '更新 後太郎',
            'isbn' => '5234567890124',
            'published_date' => '2026-04-15',
            'description' => 'API更新で保存する説明文です。',
            'image_url' => 'https://example.com/after-book.jpg',
            'genres' => [$this->firstNewGenre->id],
        ], $override);
    }

    public function test_書籍更新は有効な値で書籍とジャンルを更新する(): void
    {
        $payload = $this->validData([
            'user_id' => $this->anotherUser->id,
            'genres' => [
                $this->firstNewGenre->id,
                $this->secondNewGenre->id,
            ],
        ]);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertOk();

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

        $genres = collect($response->json('data.genres'))->keyBy('id');

        $response->assertJsonPath('message', '書籍を更新しました。');
        $response->assertJsonPath('data.id', $this->book->id);
        $response->assertJsonPath('data.user_id', $this->bookOwner->id);
        $response->assertJsonPath('data.title', 'API更新後の書籍');
        $response->assertJsonPath('data.author', '更新 後太郎');
        $response->assertJsonPath('data.isbn', '5234567890124');
        $response->assertJsonPath('data.published_date', '2026-04-15');
        $response->assertJsonPath('data.description', 'API更新で保存する説明文です。');
        $response->assertJsonPath('data.image_url', 'https://example.com/after-book.jpg');

        $this->assertCount(2, $genres);
        $this->assertSame('技術書', $genres->get($this->firstNewGenre->id)['name']);
        $this->assertSame('実用書', $genres->get($this->secondNewGenre->id)['name']);

        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->bookOwner->id,
            'title' => 'API更新後の書籍',
            'author' => '更新 後太郎',
            'isbn' => '5234567890124',
            'description' => 'API更新で保存する説明文です。',
            'image_url' => 'https://example.com/after-book.jpg',
        ]);

        $this->book->refresh();

        $this->assertSame(
            $payload['published_date'],
            $this->book->published_date->toDateString()
        );

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->oldGenre->id,
        ]);

        $this->assertDatabaseCount('book_genre', 2);
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->firstNewGenre->id,
        ]);
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->secondNewGenre->id,
        ]);
    }

    public function test_書籍更新は更新対象自身のisbnをそのまま使用できる(): void
    {
        $payload = $this->validData([
            'isbn' => $this->book->isbn,
        ]);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertOk();
        $response->assertJsonPath('message', '書籍を更新しました。');
        $response->assertJsonPath('data.isbn', '5234567890123');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'isbn' => '5234567890123',
            'title' => 'API更新後の書籍',
        ]);
    }

    public function test_書籍更新は任意項目をnullに更新できる(): void
    {
        $payload = $this->validData([
            'isbn' => '5234567890125',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertOk();

        $response->assertJsonPath('data.description', null);
        $response->assertJsonPath('data.image_url', null);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'isbn' => '5234567890125',
            'description' => null,
            'image_url' => null,
        ]);
    }

    public function test_書籍更新は任意項目が未入力でも更新できる(): void
    {
        $payload = $this->validData([
            'isbn' => '5234567890126',
        ]);

        unset($payload['description'], $payload['image_url']);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertOk();

        $response->assertJsonPath('data.description', '更新前の説明文です。');
        $response->assertJsonPath('data.image_url', 'https://example.com/before-book.jpg');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'isbn' => '5234567890126',
            'description' => '更新前の説明文です。',
            'image_url' => 'https://example.com/before-book.jpg',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->firstNewGenre->id,
        ]);
    }

    public function test_書籍更新はバリデーションエラー時に書籍とジャンル紐付けを変更しない(): void
    {
        $payload = $this->validData([
            'genres' => [],
        ]);

        unset($payload['title']);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'title',
            'genres',
        ]);
        $response->assertJsonPath('errors.title.0', 'タイトルを入力してください。');
        $response->assertJsonPath('errors.genres.0', 'ジャンルを1つ以上選択してください。');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->bookOwner->id,
            'title' => 'API更新前の書籍',
            'author' => '更新前 太郎',
            'isbn' => '5234567890123',
            'description' => '更新前の説明文です。',
            'image_url' => 'https://example.com/before-book.jpg',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->oldGenre->id,
        ]);

        $this->assertDatabaseCount('book_genre', 1);
    }

    public function test_別の書籍が使用しているisbnには更新できない(): void
    {
        Book::factory()->create([
            'user_id' => $this->bookOwner->id,
            'title' => '別の書籍',
            'isbn' => '5234567890127',
        ]);

        $payload = $this->validData([
            'isbn' => '5234567890127',
        ]);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'isbn',
        ]);
        $response->assertJsonPath('errors.isbn.0', '入力されたISBNはすでに使用されています。');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => 'API更新前の書籍',
            'isbn' => '5234567890123',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->oldGenre->id,
        ]);
    }

    public function test_isbnが13桁ではない場合は書籍更新できない(): void
    {
        $payload = $this->validData([
            'isbn' => '123456789012',
        ]);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'isbn',
        ]);
        $response->assertJsonPath('errors.isbn.0', 'ISBNは13桁で入力してください。');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'isbn' => '5234567890123',
        ]);
    }

    public function test_ジャンル未指定では書籍更新できない(): void
    {
        $payload = $this->validData([
            'isbn' => '5234567890128',
        ]);

        unset($payload['genres']);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'genres',
        ]);
        $response->assertJsonPath('errors.genres.0', 'ジャンルを1つ以上選択してください。');

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->oldGenre->id,
        ]);
    }

    public function test_存在しないジャンルでは書籍更新できない(): void
    {
        $payload = $this->validData([
            'isbn' => '5234567890129',
            'genres' => [999999],
        ]);

        $response = $this->putJson(route('api.v1.books.update', $this->book), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('message', '入力内容に誤りがあります。');
        $response->assertJsonValidationErrors([
            'genres.0',
        ]);
        $this->assertSame(
            '選択されたジャンルは存在しません。',
            $response->json('errors')['genres.0'][0]
        );

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->oldGenre->id,
        ]);
    }

    public function test_存在しない書籍更新は404を返す(): void
    {
        $response = $this->putJson(
            route('api.v1.books.update', 999999),
            $this->validData()
        );

        $response->assertNotFound();

        $response->assertJsonStructure([
            'message',
        ]);

        $response->assertJsonPath(
            'message',
            '対象の書籍が見つかりませんでした。'
        );

        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseCount('book_genre', 1);
    }
}
