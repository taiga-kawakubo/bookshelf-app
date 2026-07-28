<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーが書籍一覧画面へアクセスできる(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();

        $this->assertGuest();
    }

    public function test_未認証ユーザーが書籍詳細画面へアクセスできる(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();

        $this->assertGuest();
    }

    public function test_未認証ユーザーがランキング画面へアクセスできる(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $this->assertGuest();
    }

    public function test_存在しない書籍の詳細画面へアクセスすると404になる(): void
    {
        $response = $this->get(
            route('books.show', ['book' => 999999])
        );

        $response->assertNotFound();

        $this->assertGuest();
    }
}
