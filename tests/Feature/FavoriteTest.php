<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済ユーザーがお気に入り一覧を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');
    }

    public function test_お気に入り一覧には自分のお気に入りだけ表示される(): void
    {
        $favoriteUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $bookOwner = User::factory()->create();

        $ownBook = Book::factory()->create([
            'title' => 'お気に入り対象の書籍',
            'user_id' => $bookOwner->id,
        ]);

        $otherBook = Book::factory()->create([
            'title' => '他ユーザーのお気に入り書籍',
            'user_id' => $bookOwner->id,
        ]);

        $favoriteUser->favoriteBooks()->attach($ownBook->id);
        $otherUser->favoriteBooks()->attach($otherBook->id);

        $response = $this
            ->actingAs($favoriteUser)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');
        $response->assertViewHas('books');

        $response->assertSeeText('お気に入り対象の書籍');
        $response->assertDontSeeText('他ユーザーのお気に入り書籍');
    }

    public function test_認証済みユーザーは他のお気に入りを変更せず書籍をお気に入り登録できる(): void
    {
        $bookOwner = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $otherFavoriteUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $otherFavoriteUser->favoriteBooks()->attach($book->id);
        $favoriteUser->favoriteBooks()->attach($otherBook->id);

        $response = $this
            ->actingAs($favoriteUser)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'お気に入りに追加しました。'
        );

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $otherFavoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $otherBook->id,
        ]);

        $this->assertDatabaseCount('favorites', 3);
    }

    public function test_お気に入り登録処理でお気に入り登録と解除ができる(): void
    {
        $bookOwner = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $otherFavoriteUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $favoriteUser->favoriteBooks()->attach($book->id);
        $otherFavoriteUser->favoriteBooks()->attach($book->id);
        $favoriteUser->favoriteBooks()->attach($otherBook->id);

        $response = $this
            ->actingAs($favoriteUser)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'お気に入りを解除しました。'
        );

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $otherFavoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $otherBook->id,
        ]);

        $this->assertDatabaseCount('favorites', 2);
    }

    public function test_お気に入り登録・解除後にリダイレクト先で成功メッセージが表示される(): void
    {
        $bookOwner = User::factory()->create();
        $favoriteUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $response = $this
            ->actingAs($favoriteUser)
            ->followingRedirects()
            ->post(route('favorites.toggle', $book));

        $response->assertOk();
        $response->assertSeeText('お気に入りに追加しました。');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $response = $this
            ->actingAs($favoriteUser)
            ->followingRedirects()
            ->post(route('favorites.toggle', $book));

        $response->assertOk();
        $response->assertSeeText('お気に入りを解除しました。');

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_お気に入りがない場合もお気に入り画面が表示される(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');
        $response->assertViewHas('books');
        $response->assertSeeText(
            'お気に入りに登録された書籍はありません。'
        );
    }

    public function test_未認証ユーザーはお気に入り一覧画面へアクセスできない(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_未認証ユーザーはお気に入りを登録・解除できない(): void
    {
        $bookOwner = User::factory()->create();
        $favoriteUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseCount('favorites', 0);

        $favoriteUser->favoriteBooks()->attach($book->id);

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_存在しない書籍にはお気に入り登録できない(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', 999999));

        $response->assertNotFound();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_お気に入り書籍が10件の場合は1ページ目に10件すべて表示される(): void
    {
        $books = Book::factory()
            ->count(10)
            ->create();

        $favoriteUser = User::factory()->create();

        $favoriteUser->favoriteBooks()->attach(
            $books->pluck('id')->toArray()
        );

        $response = $this->actingAs($favoriteUser)->get(
            route('favorites.index')
        );

        $response->assertOk();

        $favoriteBooks = $response->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $favoriteBooks
        );

        $this->assertCount(10, $favoriteBooks);
        $this->assertSame(10, $favoriteBooks->total());
        $this->assertSame(10, $favoriteBooks->perPage());
        $this->assertSame(1, $favoriteBooks->currentPage());
        $this->assertSame(1, $favoriteBooks->lastPage());
    }

    public function test_お気に入り書籍が11件の場合は10件ごとにページネーションされる(): void
    {
        $books = Book::factory()
            ->count(11)
            ->create();

        $favoriteUser = User::factory()->create();

        $favoriteUser->favoriteBooks()->attach(
            $books->pluck('id')->toArray()
        );

        $firstPageResponse = $this->actingAs($favoriteUser)->get(
            route('favorites.index')
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

        $secondPageResponse = $this->actingAs($favoriteUser)->get(
            route('favorites.index', ['page' => 2])
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
