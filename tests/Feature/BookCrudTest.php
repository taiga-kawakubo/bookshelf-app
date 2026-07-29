<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    private Genre $genre;

    /**
     * 検証に必要なジャンルを作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);

        $this->genre = Genre::query()->firstOrFail();
    }

    /**
     * 正常な書籍入力データを作成する。
     * テストごとに変更したい値は、$overrideで上書きする。
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function validBookData(array $override = []): array
    {
        return array_merge([
            'title' => 'Laravel実践入門',
            'author' => '山田太郎',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-28',
            'description' => 'Laravelを学習するための書籍です。',
            'image_url' => 'https://example.com/laravel-book.jpg',
            'genres' => [$this->genre->id],
        ], $override);
    }

    /*
    |--------------------------------------------------------------------------
    | 書籍登録画面
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーは書籍登録画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.create'));

        $response->assertOk();
        $response->assertViewIs('books.create');
        $response->assertViewHas('genres');

        $response->assertSee('name="title"', false);
        $response->assertSee('name="author"', false);
        $response->assertSee('name="isbn"', false);
        $response->assertSee('name="published_date"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="image_url"', false);
        $response->assertSee('name="genres[]"', false);

        foreach (Genre::query()->get() as $genre) {
            $response->assertSeeText($genre->name);
        }
    }

    public function test_未認証ユーザーは書籍登録画面へアクセスできない(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | 書籍登録
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーは書籍を登録できる(): void
    {
        $user = User::factory()->create();

        $data = $this->validBookData();

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'description' => $data['description'],
            'image_url' => $data['image_url'],
        ]);

        $book = Book::query()
            ->where('isbn', $data['isbn'])
            ->firstOrFail();

        $this->assertSame(
            $data['published_date'],
            $book->published_date->format('Y-m-d')
        );

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_未認証ユーザーは書籍を登録できない(): void
    {
        $response = $this->post(
            route('books.store'),
            $this->validBookData()
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_必須項目が不正な場合は書籍とジャンル紐付けが登録されない(): void
    {
        $user = User::factory()->create();

        $data = $this->validBookData([
            'title' => '',
            'genres' => [],
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $data);

        $response->assertRedirect(route('books.create'));

        $response->assertSessionHasErrors([
            'title',
            'genres',
        ]);

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_isb_nが13桁でない場合は書籍を登録できない(): void
    {
        $user = User::factory()->create();

        $data = $this->validBookData([
            'isbn' => '123456789012',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $data);

        $response->assertRedirect(route('books.create'));
        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }

    public function test_登録済み_isb_nでは書籍を登録できない(): void
    {
        $user = User::factory()->create();

        $existingBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '登録済み書籍',
            'isbn' => '1111111111111',
        ]);

        $data = $this->validBookData([
            'title' => '重複ISBNで登録する書籍',
            'isbn' => $existingBook->isbn,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $data);

        $response->assertRedirect(route('books.create'));

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);

        $this->assertDatabaseHas('books', [
            'id' => $existingBook->id,
            'user_id' => $user->id,
            'title' => '登録済み書籍',
            'isbn' => '1111111111111',
        ]);

        $this->assertDatabaseMissing('books', [
            'title' => '重複ISBNで登録する書籍',
        ]);

        $this->assertDatabaseCount('book_genre', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | 書籍編集画面
    |--------------------------------------------------------------------------
    */

    public function test_書籍登録者は書籍編集画面を表示できる(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前の書籍',
            'author' => '更新前の著者',
            'isbn' => '1111111111111',
            'published_date' => '2026-07-01',
            'description' => '更新前の説明です。',
            'image_url' => 'https://example.com/before.jpg',
        ]);

        $book->genres()->attach($this->genre->id);

        $response = $this
            ->actingAs($owner)
            ->get(route('books.edit', $book));

        $response->assertOk();
        $response->assertViewIs('books.edit');

        $response->assertViewHas(
            'book',
            fn (Book $viewBook): bool => $viewBook->is($book)
        );

        $response->assertViewHas('genres');

        $response->assertSee($book->title);
        $response->assertSee($book->author);
        $response->assertSee($book->isbn);
        $response->assertSee($book->description);
        $response->assertSee($book->image_url);
        $response->assertSeeText($this->genre->name);
    }

    public function test_未認証ユーザーは書籍編集画面へアクセスできない(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->get(
            route('books.edit', $book)
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_別ユーザーは書籍編集画面へアクセスできない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | 書籍更新
    |--------------------------------------------------------------------------
    */

    public function test_書籍登録者は書籍とジャンル紐付けを更新できる(): void
    {
        $owner = User::factory()->create();

        $genres = Genre::query()
            ->take(2)
            ->get();

        $this->assertCount(2, $genres);

        $oldGenre = $genres->get(0);
        $newGenre = $genres->get(1);

        $this->assertNotNull($oldGenre);
        $this->assertNotNull($newGenre);

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前の書籍',
            'isbn' => '1111111111111',
        ]);

        $book->genres()->attach($oldGenre->id);

        $data = $this->validBookData([
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-08-01',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/after.jpg',
            'genres' => [$newGenre->id],
        ]);

        $response = $this
            ->actingAs($owner)
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            '書籍を更新しました。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => $data['title'],
            'author' => $data['author'],
            'isbn' => $data['isbn'],
            'description' => $data['description'],
            'image_url' => $data['image_url'],
        ]);

        $book->refresh();

        $this->assertSame(
            $data['published_date'],
            $book->published_date->toDateString()
        );

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }

    public function test_更新値が不正な場合は書籍とジャンル紐付けが変更されない(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '変更前の書籍',
            'isbn' => '1111111111111',
        ]);

        $book->genres()->attach($this->genre->id);

        $data = $this->validBookData([
            'title' => '',
            'isbn' => $book->isbn,
            'genres' => [],
        ]);

        $response = $this
            ->actingAs($owner)
            ->from(route('books.edit', $book))
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(
            route('books.edit', $book)
        );

        $response->assertSessionHasErrors([
            'title',
            'genres',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '変更前の書籍',
            'isbn' => '1111111111111',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);

        $this->assertDatabaseCount('book_genre', 1);
    }

    public function test_別の書籍が使用している_isb_nには更新できない(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新対象書籍',
            'isbn' => '1111111111111',
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '別の書籍',
            'isbn' => '2222222222222',
        ]);

        $book->genres()->attach($this->genre->id);

        $data = $this->validBookData([
            'isbn' => $otherBook->isbn,
        ]);

        $response = $this
            ->actingAs($owner)
            ->from(route('books.edit', $book))
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(
            route('books.edit', $book)
        );

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新対象書籍',
            'isbn' => '1111111111111',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_未認証ユーザーは書籍を更新できない(): void
    {
        $owner = User::factory()->create();

        $newGenre = Genre::query()
            ->where('id', '!=', $this->genre->id)
            ->firstOrFail();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '変更前の書籍',
        ]);

        $book->genres()->attach($this->genre->id);

        $data = $this->validBookData([
            'title' => '不正に変更された書籍',
            'isbn' => $book->isbn,
            'genres' => [$newGenre->id],
        ]);

        $response = $this->put(
            route('books.update', $book),
            $data
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        // 書籍本体が変更されていない。
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '変更前の書籍',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正に変更された書籍',
        ]);

        // 更新前のジャンル紐付けが残っている。
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);

        // 送信したジャンルとの紐付けは作成されていない。
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseCount('book_genre', 1);
    }

    public function test_別ユーザーは書籍を更新できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $newGenre = Genre::query()
            ->where('id', '!=', $this->genre->id)
            ->firstOrFail();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '変更前の書籍',
        ]);

        $book->genres()->attach($this->genre->id);

        $data = $this->validBookData([
            'title' => '不正に変更された書籍',
            'isbn' => $book->isbn,
            'genres' => [$newGenre->id],
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('books.update', $book), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '変更前の書籍',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正に変更された書籍',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseCount('book_genre', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | 書籍削除
    |--------------------------------------------------------------------------
    */

    public function test_書籍登録者は書籍と関連データを削除できる(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $likeUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $book->genres()->attach($this->genre->id);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '削除確認用レビューです。',
        ]);

        $favoriteUser
            ->favoriteBooks()
            ->attach($book->id);

        $likeUser
            ->likedReviews()
            ->attach($review->id);

        $response = $this
            ->actingAs($owner)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );

        $this->assertDatabaseMissing('books', ['id' => $book->id]);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);

        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);

        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);

        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);

        $this->assertDatabaseHas('genres', ['id' => $this->genre->id]);
    }

    public function test_未認証ユーザーは書籍を削除できない(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->delete(
            route('books.destroy', $book)
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_別ユーザーは書籍を削除できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $book->genres()->attach($this->genre->id);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);
    }
}
