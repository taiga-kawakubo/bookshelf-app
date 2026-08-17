<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnLookupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはISBNから書籍情報を取得できる(): void
    {
        $user = User::factory()->create();
        $isbn = '9781234567890';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'ISBN検索の書籍',
                            'authors' => ['山田太郎', '佐藤花子'],
                            'publishedDate' => '2026-01-01',
                            'description' => 'ISBN検索で取得した説明文です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/isbn-book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.show', ['isbn' => $isbn]));

        $response->assertOk();
        $response->assertJson([
            'title' => 'ISBN検索の書籍',
            'author' => '山田太郎, 佐藤花子',
            'isbn' => $isbn,
            'published_date' => '2026-01-01',
            'description' => 'ISBN検索で取得した説明文です。',
            'image_url' => 'https://example.com/isbn-book.jpg',
        ]);

        Http::assertSent(function ($request) use ($isbn): bool {
            return str_contains(
                $request->url(),
                'https://www.googleapis.com/books/v1/volumes'
            )
                && $request['q'] === 'isbn:'.$isbn
                && $request['maxResults'] === 1;
        });
    }

    public function test_13桁以外のISBNはバリデーションエラーを返す(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.show', ['isbn' => '123456789']));

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'ISBNは13桁で入力してください。',
        ]);

        Http::assertNothingSent();
    }

    public function test_外部APIが失敗した場合は502を返す(): void
    {
        $user = User::factory()->create();
        $isbn = '9781234567890';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.show', ['isbn' => $isbn]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    public function test_外部APIに書籍がない場合は404を返す(): void
    {
        $user = User::factory()->create();
        $isbn = '9781234567890';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.show', ['isbn' => $isbn]));

        $response->assertNotFound();
        $response->assertJson([
            'error' => '書籍情報が見つかりませんでした。',
        ]);
    }

    public function test_書籍情報の任意項目がない場合は初期値を返す(): void
    {
        $user = User::factory()->create();
        $isbn = '9781234567890';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.isbn.show', ['isbn' => $isbn]));

        $response->assertOk();
        $response->assertJson([
            'title' => '',
            'author' => '',
            'isbn' => $isbn,
            'published_date' => null,
            'description' => '',
            'image_url' => '',
        ]);
    }

    public function test_未認証ユーザーはISBN検索を利用できない(): void
    {
        $response = $this->get(
            route('books.isbn.show', ['isbn' => '9781234567890'])
        );

        $response->assertRedirect(route('login'));
    }
}
