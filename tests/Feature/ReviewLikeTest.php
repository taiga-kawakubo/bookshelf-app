<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーは他のいいねを変更せずレビューにいいねできる(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $likeUser = User::factory()->create();
        $otherReviewer = User::factory()->create();
        $otherLikeUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'いいね対象レビューです。',
        ]);

        $otherReview = $book->reviews()->create([
            'user_id' => $otherReviewer->id,
            'rating' => 4,
            'comment' => '対象外のレビューです。',
        ]);

        $otherLikeUser->likedReviews()->attach($review->id);
        $likeUser->likedReviews()->attach($otherReview->id);

        $response = $this
            ->actingAs($likeUser)
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューにいいねしました。'
        );

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $otherLikeUser->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $otherReview->id,
        ]);

        $this->assertDatabaseCount('review_likes', 3);
    }

    public function test_いいね済みレビューに再度いいねすると対象のいいねだけ解除できる(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $likeUser = User::factory()->create();
        $otherReviewer = User::factory()->create();
        $otherLikeUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => 'いいね解除対象レビューです。',
        ]);

        $otherReview = $book->reviews()->create([
            'user_id' => $otherReviewer->id,
            'rating' => 3,
            'comment' => '削除対象ではないレビューです。',
        ]);

        $likeUser->likedReviews()->attach($review->id);
        $otherLikeUser->likedReviews()->attach($review->id);
        $likeUser->likedReviews()->attach($otherReview->id);

        $response = $this
            ->actingAs($likeUser)
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューのいいねを解除しました。'
        );

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $otherLikeUser->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $otherReview->id,
        ]);

        $this->assertDatabaseCount('review_likes', 2);
    }

    public function test_未認証ユーザーはレビューいいねを登録解除できない(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();
        $likeUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $bookOwner->id,
        ]);

        $review = $book->reviews()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => '未認証確認用レビューです。',
        ]);

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseCount('review_likes', 0);

        $likeUser->likedReviews()->attach($review->id);

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseCount('review_likes', 1);
    }

    public function test_存在しないレビューにはいいねできない(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.like', 999999));

        $response->assertNotFound();

        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_レビューいいね登録解除後にリダイレクト先で成功メッセージが表示される(): void
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
            'comment' => 'メッセージ確認用レビューです。',
        ]);

        $response = $this
            ->actingAs($likeUser)
            ->followingRedirects()
            ->post(route('reviews.like', $review));

        $response->assertOk();
        $response->assertSeeText('レビューにいいねしました。');

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);

        $response = $this
            ->actingAs($likeUser)
            ->followingRedirects()
            ->post(route('reviews.like', $review));

        $response->assertOk();
        $response->assertSeeText('レビューのいいねを解除しました。');

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $likeUser->id,
            'review_id' => $review->id,
        ]);
    }
}
