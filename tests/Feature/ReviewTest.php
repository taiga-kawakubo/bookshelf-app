<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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
            'rating' => 5,
            'comment' => 'とても面白かったです。',
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

        $this->assertDatabaseCount('reviews', 1);

        $this->assertDatabaseHas('reviews', [
            'id' => $existingReview->id,
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '最初に投稿したレビューです。',
        ]);

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

        $response->assertViewHas('review',fn (Review $reviewFromView): bool 
            =>$reviewFromView->is($review));

        $response->assertSee(
            'value="' . $review->rating . '"',
            false
        );

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

    public function test_存在しないレビューIDでは編集画面を表示できない(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reviews.edit', 999999));

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | レビュー更新
    |--------------------------------------------------------------------------
    */

    public function test_レビュー投稿者は対象レビューだけを更新できる(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $otherReviewer = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        $otherReview = $book->reviews()->create([
            'user_id' => $otherReviewer->id,
            'rating' => 2,
            'comment' => '更新対象外レビューです。',
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '更新後のレビューです。',
            ]);

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
            'rating' => 5,
            'comment' => '更新後のレビューです。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $otherReview->id,
            'book_id' => $book->id,
            'user_id' => $otherReviewer->id,
            'rating' => 2,
            'comment' => '更新対象外レビューです。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '更新前のレビューです。',
        ]);

        $this->assertDatabaseCount('reviews', 2);
    }

    public function test_必須項目が不足している場合はレビューを更新できない(): void
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
                'rating' => '',
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

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '削除対象レビューです。',
        ]);

        $otherReview = $book->reviews()->create([
            'user_id' => $otherReviewer->id,
            'rating' => 4,
            'comment' => '削除対象外レビューです。',
        ]);

        $likeUser
            ->likedReviews()
            ->attach($review->id);

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

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $otherReview->id,
            'book_id' => $book->id,
            'user_id' => $otherReviewer->id,
            'rating' => 4,
            'comment' => '削除対象外レビューです。',
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $otherLikeUser->id,
            'review_id' => $otherReview->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseCount('review_likes', 1);
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
            'rating' => 5,
            'comment' => '削除されないレビューです。',
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
            'rating' => 5,
            'comment' => '削除されないレビューです。',
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);
    }
}