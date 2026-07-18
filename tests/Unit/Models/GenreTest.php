<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_ジャンルは複数の書籍と結びつく(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $book1 = Book::create([
            'user_id' => $user1->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-07-17',
            'description' => 'リレーションテスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        $book2 = Book::create([
            'user_id' => $user2->id,
            'title' => 'テスト書籍2',
            'author' => 'テスト著者2',
            'isbn' => '9781234567891',
            'published_date' => '2026-07-18',
            'description' => 'リレーションテスト用の書籍2です。',
            'image_url' => 'https://example.com/book2.jpg',
        ]);

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $genre->books()->sync([
            $book1->id,
            $book2->id,
        ]);

        $genre->load('books');

        $this->assertCount(2, $genre->books);
        $this->assertEqualsCanonicalizing(
            [
                $book1->id,
                $book2->id,
            ],
            $genre->books->pluck('id')->all()
        );
    }
}
