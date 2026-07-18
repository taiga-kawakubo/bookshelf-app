<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_このユーザーと結びつく書籍を取得する(): void
    {
        $user = User::factory()->create();
        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-07-17',
            'description' => 'リレーションテスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        $user->load('books');
        $this->assertCount(1, $user->books);

        $this->assertTrue(
            $user->books->contains($book)
        );
    }

    public function test_このユーザーと結びつくレビューを取得する(): void
    {
        // レビュー投稿者
        $user = User::factory()->create();

        // 書籍登録者
        $bookOwner = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-07-17',
            'description' => 'リレーションテスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        $review = Review::create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $user->load('reviews');

        $this->assertCount(1, $user->reviews);

        $this->assertTrue(
            $user->reviews->contains($review)
        );
    }

    public function test_このユーザーと結びつくお気に入りを取得する(): void
    {
        $user = User::factory()->create();

        $bookOwner = User::factory()->create();

        $book1 = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'テスト書籍1',
            'author' => 'テスト著者1',
            'isbn' => '9781234567890',
            'published_date' => '2026-07-17',
            'description' => 'お気に入りテスト用の書籍1です。',
            'image_url' => 'https://example.com/book1.jpg',
        ]);

        $book2 = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'テスト書籍2',
            'author' => 'テスト著者2',
            'isbn' => '9781234567891',
            'published_date' => '2026-07-18',
            'description' => 'お気に入りテスト用の書籍2です。',
            'image_url' => 'https://example.com/book2.jpg',
        ]);

        $user->favoriteBooks()->sync([
            $book1->id,
            $book2->id,
        ]);

        $user->load('favoriteBooks');

        $this->assertCount(2, $user->favoriteBooks);

        $this->assertEqualsCanonicalizing(
            [
                $book1->id,
                $book2->id,
            ],
            $user->favoriteBooks->pluck('id')->all()
        );
    }

    public function test_このユーザーと結びつくレビューのいいねを取得する(): void
    {
        // レビューへ「いいね」するユーザー
        $likingUser = User::factory()->create();

        // 書籍を登録するユーザー
        $bookOwner = User::factory()->create();

        $book = Book::create([
            'user_id' => $bookOwner->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-07-17',
            'description' => 'レビューいいねテスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        // レビュー投稿者を2人作成
        $reviewAuthor1 = User::factory()->create();
        $reviewAuthor2 = User::factory()->create();

        $review1 = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewAuthor1->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $review2 = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewAuthor2->id,
            'rating' => 4,
            'comment' => '読みやすい本でした。',
        ]);

        $likingUser->likedReviews()->sync([
            $review1->id,
            $review2->id,
        ]);

        $likingUser->load('likedReviews');

        $this->assertCount(2, $likingUser->likedReviews);

        $this->assertEqualsCanonicalizing(
            [
                $review1->id,
                $review2->id,
            ],
            $likingUser->likedReviews->pluck('id')->all()
        );
    }
}
