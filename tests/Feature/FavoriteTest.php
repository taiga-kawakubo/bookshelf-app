<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_お気に入り済み書籍に再度実行すると対象のお気に入りだけ解除できる(): void
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

    public function test_未認証ユーザーはお気に入りを登録解除できない(): void
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

    public function test_お気に入り登録解除後にリダイレクト先で成功メッセージが表示される(): void
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
}
