<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍削除_ap_iは指定した書籍と関連データを削除する(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $likeUser = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $targetBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '削除対象の書籍',
            'isbn' => '6234567890123',
        ]);

        $otherBook = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '削除対象ではない書籍',
            'isbn' => '6234567890124',
        ]);

        $targetBook->genres()->attach($genre->id);
        $otherBook->genres()->attach($genre->id);

        $targetReview = $targetBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '削除対象書籍のレビューです。',
        ]);

        $otherReview = $otherBook->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '削除対象ではない書籍のレビューです。',
        ]);

        $favoriteUser->favoriteBooks()->attach($targetBook->id);
        $favoriteUser->favoriteBooks()->attach($otherBook->id);

        $likeUser->likedReviews()->attach($targetReview->id);
        $likeUser->likedReviews()->attach($otherReview->id);

        $response = $this->deleteJson(route('api.v1.books.destroy', $targetBook));

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $targetBook->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $targetBook->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $targetReview->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $targetBook->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $targetReview->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $otherBook->id,
            'title' => '削除対象ではない書籍',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $otherBook->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $otherReview->id,
            'book_id' => $otherBook->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'book_id' => $otherBook->id,
            'user_id' => $favoriteUser->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'review_id' => $otherReview->id,
            'user_id' => $likeUser->id,
        ]);
    }

    public function test_存在しない書籍削除_ap_iは404を返す(): void
    {
        $bookOwner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
            'title' => '削除されない書籍',
            'isbn' => '6234567890125',
        ]);

        $response = $this->deleteJson(route('api.v1.books.destroy', 999999));

        $response->assertNotFound();

        $response->assertJsonStructure([
            'message',
        ]);

        $response->assertJsonPath(
            'message',
            '対象の書籍が見つかりませんでした。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '削除されない書籍',
        ]);
    }
}
