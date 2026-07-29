<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 検証に必要なジャンルを作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);
    }

    public function test_書籍一覧画面に書籍情報と紐づく複数ジャンルが表示される(): void
    {
        $genres = Genre::query()
            ->take(2)
            ->get();

        $this->assertCount(2, $genres);

        $book = Book::factory()->create([
            'title' => 'Laravelテスト入門',
            'author' => '山田太郎',
        ]);

        $book->genres()->attach(
            $genres->pluck('id')->all()
        );

        $response = $this->get(route('books.index'));

        $response->assertOk();

        $response->assertSeeText($book->title);
        $response->assertSeeText($book->author);

        foreach ($genres as $genre) {
            $response->assertSeeText($genre->name);
        }
    }
    public function test_書籍が10件の場合は1ページ目に10件すべて表示される(): void
    {
        Book::factory()
            ->count(10)
            ->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $books
        );

        $this->assertCount(10, $books);
        $this->assertSame(10, $books->total());
        $this->assertSame(1, $books->currentPage());
        $this->assertSame(1, $books->lastPage());
    }

    public function test_書籍が11件の場合は10件ごとにページネーションされる(): void
    {
        Book::factory()
            ->count(11)
            ->create();

        $firstPageResponse = $this->get(
            route('books.index')
        );

        $firstPageResponse->assertOk();

        $firstPageBooks = $firstPageResponse->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $firstPageBooks
        );

        $this->assertCount(10, $firstPageBooks);
        $this->assertSame(11, $firstPageBooks->total());
        $this->assertSame(1, $firstPageBooks->currentPage());
        $this->assertSame(2, $firstPageBooks->lastPage());

        $secondPageResponse = $this->get(
            route('books.index', ['page' => 2])
        );

        $secondPageResponse->assertOk();

        $secondPageBooks = $secondPageResponse->viewData('books');

        $this->assertInstanceOf(
            LengthAwarePaginator::class,
            $secondPageBooks
        );

        $this->assertCount(1, $secondPageBooks);
        $this->assertSame(11, $secondPageBooks->total());
        $this->assertSame(2, $secondPageBooks->currentPage());
        $this->assertSame(2, $secondPageBooks->lastPage());
    }
}