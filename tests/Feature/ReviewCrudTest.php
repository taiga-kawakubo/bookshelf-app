<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCrudTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | レビュー投稿
    |--------------------------------------------------------------------------
    */

    public function test_認証済みユーザーはレビューを投稿できる(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $data = [
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ];

        $response = $this
            ->actingAs($reviewer)
            ->post(route('reviews.store', $book), $data);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );

        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ]);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_同じユーザーは同じ書籍へレビューを重複投稿できない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $existingReview = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '最初に投稿したレビューです。',
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => '重複して投稿するレビューです。',
            ]);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'error',
            'この書籍にはすでにレビューを投稿しています。'
        );

        // 最初に投稿したレビューだけが残っている。
        $this->assertDatabaseCount('reviews', 1);

        $this->assertDatabaseHas('reviews', [
            'id' => $existingReview->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '最初に投稿したレビューです。',
        ]);

        // 重複して送信したレビューは保存されていない。
        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'comment' => '重複して投稿するレビューです。',
        ]);
    }

    public function test_未認証ユーザーはレビューを投稿できない(): void
    {
        $bookOwner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $response = $this->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => '投稿できないレビューです。',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_必須項目が不足している場合はレビューを投稿できない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'rating' => '',
                'comment' => '',
            ]);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_レビュー投稿値が範囲や最大文字数を超える場合は保存されない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'rating' => 6,
                'comment' => str_repeat('あ', 1001),
            ]);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | レビュー編集画面
    |--------------------------------------------------------------------------
    */

    public function test_レビュー投稿者はレビュー編集画面を表示できる(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '読みやすい本でした。',
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertViewIs('reviews.edit');

        $response->assertViewHas(
            'review',
            fn (Review $viewReview): bool => $viewReview->is($review)
        );

        $response->assertSee((string) $review->rating);
        $response->assertSeeText($review->comment);
    }

    public function test_未認証ユーザーはレビュー編集画面へアクセスできない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '編集対象レビューです。',
        ]);

        $response = $this->get(
            route('reviews.edit', $review)
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_別ユーザーはレビュー編集画面へアクセスできない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '編集対象レビューです。',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | レビュー更新
    |--------------------------------------------------------------------------
    */

    public function test_レビュー投稿者はレビューを更新できる(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        $data = [
            'rating' => 5,
            'comment' => '更新後のレビューです。',
        ];

        $response = $this
            ->actingAs($reviewer)
            ->put(route('reviews.update', $review), $data);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '更新前のレビューです。',
        ]);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_更新値が不正な場合はレビューが変更されない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '変更前のレビューです。',
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), [
                'rating' => 6,
                'comment' => '',
            ]);

        $response->assertRedirect(
            route('reviews.edit', $review)
        );

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '変更前のレビューです。',
        ]);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_未認証ユーザーはレビューを更新できない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '変更前のレビューです。',
        ]);

        $response = $this->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => '不正に変更されたレビューです。',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '変更前のレビューです。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '不正に変更されたレビューです。',
        ]);
    }

    public function test_別ユーザーはレビューを更新できない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '変更前のレビューです。',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '不正に変更されたレビューです。',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '変更前のレビューです。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '不正に変更されたレビューです。',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | レビュー削除
    |--------------------------------------------------------------------------
    */

    public function test_レビュー投稿者は対象レビューと関連いいねだけを削除できる(): void
    {
        $bookOwner = User::factory()->create();

        $reviewer = User::factory()->create();
        $otherReviewer = User::factory()->create();

        $likeUser = User::factory()->create();
        $otherLikeUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        // 削除対象のレビュー
        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '削除対象レビューです。',
        ]);

        // 削除対象ではないレビュー
        $otherReview = $book->reviews()->create([
            'user_id' => $otherReviewer->id,
            'rating' => 4,
            'comment' => '削除対象ではないレビューです。',
        ]);

        // 削除対象レビューへのいいね
        $likeUser
            ->likedReviews()
            ->attach($review->id);

        // 削除対象ではないレビューへのいいね
        $otherLikeUser
            ->likedReviews()
            ->attach($otherReview->id);

        $response = $this
            ->actingAs($reviewer)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );

        // 削除対象のレビューが削除されている。
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        // 削除対象レビューへのいいねが削除されている。
        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);

        // 削除対象ではないレビューは残っている。
        $this->assertDatabaseHas('reviews', [
            'id' => $otherReview->id,
            'book_id' => $book->id,
            'user_id' => $otherReviewer->id,
            'rating' => 4,
            'comment' => '削除対象ではないレビューです。',
        ]);

        // 削除対象ではないレビューへのいいねは残っている。
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $otherLikeUser->id,
            'review_id' => $otherReview->id,
        ]);

        // 対象外の1件だけが残っている。
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseCount('review_likes', 1);

        // レビューを削除しても書籍本体は残っている。
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_未認証ユーザーはレビューと関連いいねを削除できない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $likeUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '削除されないレビューです。',
        ]);

        $likeUser
            ->likedReviews()
            ->attach($review->id);

        $response = $this->delete(
            route('reviews.destroy', $review)
        );

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_別ユーザーはレビューと関連いいねを削除できない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $otherUser = User::factory()->create();
        $likeUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '削除されないレビューです。',
        ]);

        $likeUser
            ->likedReviews()
            ->attach($review->id);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);
    }
}
