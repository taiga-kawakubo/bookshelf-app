<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_レビューは結びつく書籍を持つ(): void
    {
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

        $reviewUser = User::factory()->create();

        $review = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewUser->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $review->load('book');

        $this->assertTrue(
            $review->book->is($book)
        );
    }

    public function test_レビューは投稿者を持つ(): void
    {
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

        $reviewUser = User::factory()->create();

        $review = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewUser->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $review->load('user');

        $this->assertTrue(
            $review->user->is($reviewUser)
        );
    }

    public function test_レビューはユーザーからのいいねを持つ(): void
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

        $reviewUser = User::factory()->create();

        $review = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewUser->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $likedByUser1 = User::factory()->create();
        $likedByUser2 = User::factory()->create();

        $review->likedByUsers()->sync([
            $likedByUser1->id,
            $likedByUser2->id,
        ]);

        $review->load('likedByUsers');

        $this->assertCount(2, $review->likedByUsers);

        $this->assertEqualsCanonicalizing(
            [
                $likedByUser1->id,
                $likedByUser2->id,
            ],
            $review->likedByUsers->pluck('id')->all()
        );
    }
}
