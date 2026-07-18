<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍は複数のジャンルを持つ(): void
    {
        $genre1 = Genre::create([
            'name' => '小説',
        ]);

        $genre2 = Genre::create([
            'name' => 'ビジネス',
        ]);

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

        $book->genres()->sync([
            $genre1->id,
            $genre2->id,
        ]);

        $book->load('genres');

        $this->assertCount(2, $book->genres);
        $this->assertEqualsCanonicalizing(
            [
                $genre1->id,
                $genre2->id,
            ],
            $book->genres->pluck('id')->all()
        );
    }

    public function test_書籍は登録したユーザーを取得する(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-07-17',
            'description' => 'リレーションテスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        $book->load('user');

        $this->assertTrue(
            $book->user->is($user)
        );
    }

    public function test_書籍は複数のレビューを持つ(): void
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

        $reviewUser1 = User::factory()->create();
        $reviewUser2 = User::factory()->create();

        $review1 = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewUser1->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $review2 = Review::create([
            'book_id' => $book->id,
            'user_id' => $reviewUser2->id,
            'rating' => 4,
            'comment' => '読みやすい本でした。',
        ]);

        $book->load('reviews');

        $this->assertCount(2, $book->reviews);

        $this->assertEqualsCanonicalizing(
            [
                $review1->id,
                $review2->id,
            ],
            $book->reviews->pluck('id')->all()
        );
    }

    public function test_書籍は複数のお気に入りしたユーザーを持つ(): void
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

        $favoriteUser1 = User::factory()->create();
        $favoriteUser2 = User::factory()->create();

        $book->favoritedUsers()->sync([
            $favoriteUser1->id,
            $favoriteUser2->id,
        ]);

        $book->load('favoritedUsers');

        $this->assertCount(2, $book->favoritedUsers);

        $this->assertEqualsCanonicalizing(
            [
                $favoriteUser1->id,
                $favoriteUser2->id,
            ],
            $book->favoritedUsers->pluck('id')->all()
        );
    }
}
