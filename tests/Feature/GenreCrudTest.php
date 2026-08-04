<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class GenreCrudTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | ジャンル一覧
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーはジャンル一覧と書籍数を表示できる(): void
    {
        $user = User::factory()->create();

        $firstGenre = Genre::query()->create([
            'name' => 'プログラミング',
        ]);

        $secondGenre = Genre::query()->create([
            'name' => 'ビジネス',
        ]);

        $firstBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $secondBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $thirdBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $firstGenre->books()->attach([
            $firstBook->id,
            $secondBook->id,
        ]);

        $secondGenre->books()->attach($thirdBook->id);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');

        $response->assertSeeText($firstGenre->name);
        $response->assertSeeText($secondGenre->name);

        $response->assertViewHas('genres', function ($genres) use ($firstGenre, $secondGenre): bool {
            $firstGenreFromView = $genres->firstWhere(
                'id',
                $firstGenre->id
            );

            $secondGenreFromView = $genres->firstWhere(
                'id',
                $secondGenre->id
            );

            return $firstGenreFromView !== null
                && $secondGenreFromView !== null
                && (int) $firstGenreFromView->books_count === 2
                && (int) $secondGenreFromView->books_count === 1;
        }
        );
    }

    public function test_未認証ユーザーはジャンル一覧へアクセスできない(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | ジャンル詳細
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーは対象ジャンルに紐づく書籍だけを表示できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::query()->create([
            'name' => 'プログラミング',
        ]);

        $otherGenre = Genre::query()->create([
            'name' => 'ビジネス',
        ]);

        $firstTargetBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '対象ジャンル書籍1',
        ]);

        $secondTargetBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '対象ジャンル書籍2',
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '別ジャンル専用書籍',
        ]);

        $firstTargetBook->genres()->attach($genre->id);
        $secondTargetBook->genres()->attach($genre->id);
        $otherBook->genres()->attach($otherGenre->id);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');

        $response->assertViewHas(
            'genre',
            fn (Genre $viewGenre): bool => $viewGenre->is($genre)
        );

        $response->assertViewHas('books');

        $response->assertSeeText($genre->name);
        $response->assertSeeText($firstTargetBook->title);
        $response->assertSeeText($secondTargetBook->title);
        $response->assertDontSeeText($otherBook->title);
    }

    public function test_未認証ユーザーはジャンル詳細へアクセスできない(): void
    {
        $genre = Genre::query()->create([
            'name' => 'プログラミング',
        ]);

        $response = $this->get(
            route('genres.show', $genre)
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_対象ジャンルの書籍が10件の場合は1ページ目に10件すべて表示される(): void
    {
        $user = User::factory()->create();

        $genre = Genre::query()->create([
            'name' => 'プログラミング',
        ]);

        $createdBooks = Book::factory()
            ->count(10)
            ->create([
                'user_id' => $user->id,
            ]);

        foreach ($createdBooks as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

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

    public function test_対象ジャンルの書籍が11件の場合は10件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::query()->create([
            'name' => 'プログラミング',
        ]);

        $createdBooks = Book::factory()
            ->count(11)
            ->create([
                'user_id' => $user->id,
            ]);

        foreach ($createdBooks as $book) {
            $book->genres()->attach($genre->id);
        }

        $firstPageResponse = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

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

        $secondPageResponse = $this
            ->actingAs($user)
            ->get(route('genres.show', [
                'genre' => $genre,
                'page' => 2,
            ]));

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

    /*
    |--------------------------------------------------------------------------
    | ジャンル登録画面
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーはジャンル登録画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('genres.create'));

        $response->assertOk();
        $response->assertViewIs('genres.create');

        $response->assertSee('name="name"', false);
    }

    public function test_未認証ユーザーはジャンル登録画面へアクセスできない(): void
    {
        $response = $this->get(route('genres.create'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | ジャンル登録
    |--------------------------------------------------------------------------
    */

    public function test_未認証ユーザーはジャンルを登録できず既存ジャンルも変更されない(): void
    {
        $existingGenre = Genre::query()->create([
            'name' => '既存ジャンル',
        ]);

        $response = $this->post(
            route('genres.store'),
            [
                'name' => '不正に登録するジャンル',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseMissing('genres', [
            'name' => '不正に登録するジャンル',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $existingGenre->id,
            'name' => '既存ジャンル',
        ]);

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_ジャンル名が未入力の場合はバリデーションエラーとなり保存されない(): void
    {
        $user = User::factory()->create();

        $existingGenre = Genre::query()->create([
            'name' => '既存ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), [
                'name' => '',
            ]);

        $response->assertRedirect(route('genres.create'));

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('genres', [
            'name' => '',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $existingGenre->id,
            'name' => '既存ジャンル',
        ]);

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_認証済みユーザーは既存ジャンルを変更せず新しいジャンルを登録できる(): void
    {
        $user = User::factory()->create();

        $existingGenre = Genre::query()->create([
            'name' => '既存ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '新規ジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを登録しました。'
        );

        $this->assertDatabaseHas('genres', [
            'name' => '新規ジャンル',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $existingGenre->id,
            'name' => '既存ジャンル',
        ]);

        $this->assertDatabaseCount('genres', 2);

        $indexResponse = $this->get(
            route('genres.index')
        );

        $indexResponse->assertOk();

        $indexResponse->assertSeeText(
            'ジャンルを登録しました。'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ジャンル編集画面
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーは現在のジャンル名が表示された編集画面を表示できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::query()->create([
            'name' => '編集前ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.edit');

        $response->assertViewHas(
            'genre',
            fn (Genre $viewGenre): bool => $viewGenre->is($genre)
        );

        $response->assertSee(
            'value="'.$genre->name.'"',
            false
        );
    }

    public function test_未認証ユーザーはジャンル編集画面へアクセスできない(): void
    {
        $genre = Genre::query()->create([
            'name' => '編集対象ジャンル',
        ]);

        $response = $this->get(
            route('genres.edit', $genre)
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | ジャンル更新
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーは対象ジャンルだけを更新できる(): void
    {
        $user = User::factory()->create();

        $targetGenre = Genre::query()->create([
            'name' => '更新前ジャンル',
        ]);

        $otherGenre = Genre::query()->create([
            'name' => '対象外ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $targetGenre), [
                'name' => '更新後ジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを更新しました。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $targetGenre->id,
            'name' => '更新後ジャンル',
        ]);

        $this->assertDatabaseMissing('genres', [
            'id' => $targetGenre->id,
            'name' => '更新前ジャンル',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $otherGenre->id,
            'name' => '対象外ジャンル',
        ]);

        $this->assertDatabaseCount('genres', 2);

        $indexResponse = $this->get(route('genres.index'));

        $indexResponse->assertSeeText(
            'ジャンルを更新しました。'
        );
    }

    public function test_未認証ユーザーはジャンルを更新できない(): void
    {
        $targetGenre = Genre::query()->create([
            'name' => '更新前ジャンル',
        ]);

        $otherGenre = Genre::query()->create([
            'name' => '対象外ジャンル',
        ]);

        $response = $this->put(
            route('genres.update', $targetGenre),
            [
                'name' => '不正に更新されたジャンル',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseHas('genres', [
            'id' => $targetGenre->id,
            'name' => '更新前ジャンル',
        ]);

        $this->assertDatabaseMissing('genres', [
            'id' => $targetGenre->id,
            'name' => '不正に更新されたジャンル',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $otherGenre->id,
            'name' => '対象外ジャンル',
        ]);

        $this->assertDatabaseCount('genres', 2);
    }

    public function test_ジャンル名が未入力の場合はバリデーションエラーとなり更新されない(): void
    {
        $user = User::factory()->create();

        $targetGenre = Genre::query()->create([
            'name' => '更新前ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('genres.edit', $targetGenre))
            ->put(route('genres.update', $targetGenre), [
                'name' => '',
            ]);

        $response->assertRedirect(
            route('genres.edit', $targetGenre)
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $targetGenre->id,
            'name' => '更新前ジャンル',
        ]);

        $this->assertDatabaseMissing('genres', [
            'id' => $targetGenre->id,
            'name' => '',
        ]);

        $this->assertDatabaseCount('genres', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | ジャンル削除
    |--------------------------------------------------------------------------
    */

    public function test_書籍との紐付けがないジャンルだけを削除できる(): void
    {
        $user = User::factory()->create();

        $deleteTargetGenre = Genre::query()->create([
            'name' => '削除対象ジャンル',
        ]);

        $otherGenre = Genre::query()->create([
            'name' => '対象外ジャンル',
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherBook->genres()->attach($otherGenre->id);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $deleteTargetGenre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );

        $this->assertDatabaseMissing('genres', [
            'id' => $deleteTargetGenre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $otherGenre->id,
            'name' => '対象外ジャンル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $otherBook->id,
            'genre_id' => $otherGenre->id,
        ]);

        $this->assertDatabaseCount('genres', 1);
        $this->assertDatabaseCount('book_genre', 1);

        $indexResponse = $this->get(route('genres.index'));

        $indexResponse->assertSeeText(
            'ジャンルを削除しました。'
        );
    }

    public function test_書籍に使用されているジャンルは削除できない(): void
    {
        $user = User::factory()->create();

        $targetGenre = Genre::query()->create([
            'name' => '使用中ジャンル',
        ]);

        $otherGenre = Genre::query()->create([
            'name' => '対象外ジャンル',
        ]);

        $targetBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $targetBook->genres()->attach($targetGenre->id);
        $otherBook->genres()->attach($otherGenre->id);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $targetGenre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            'このジャンルは書籍に使用されているため削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $targetGenre->id,
            'name' => '使用中ジャンル',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $otherGenre->id,
            'name' => '対象外ジャンル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $targetBook->id,
            'genre_id' => $targetGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $otherBook->id,
            'genre_id' => $otherGenre->id,
        ]);

        $this->assertDatabaseCount('genres', 2);
        $this->assertDatabaseCount('book_genre', 2);

        $indexResponse = $this->get(route('genres.index'));

        $indexResponse->assertSeeText(
            'このジャンルは書籍に使用されているため削除できません。'
        );
    }

    public function test_未認証ユーザーはジャンルを削除できず関連データも変更されない(): void
    {
        $owner = User::factory()->create();

        $targetGenre = Genre::query()->create([
            'name' => '削除対象ジャンル',
        ]);

        $otherGenre = Genre::query()->create([
            'name' => '対象外ジャンル',
        ]);

        $targetBook = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $targetBook->genres()->attach($targetGenre->id);
        $otherBook->genres()->attach($otherGenre->id);

        $response = $this->delete(
            route('genres.destroy', $targetGenre)
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseHas('genres', [
            'id' => $targetGenre->id,
            'name' => '削除対象ジャンル',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $otherGenre->id,
            'name' => '対象外ジャンル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $targetBook->id,
            'genre_id' => $targetGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $otherBook->id,
            'genre_id' => $otherGenre->id,
        ]);

        $this->assertDatabaseCount('genres', 2);
        $this->assertDatabaseCount('book_genre', 2);
    }

    /*
    |--------------------------------------------------------------------------
    | 存在しないジャンル
    |--------------------------------------------------------------------------
    */

    public function test_存在しないジャンルの詳細画面は404になる(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', 999999));

        $response->assertNotFound();
    }
}
