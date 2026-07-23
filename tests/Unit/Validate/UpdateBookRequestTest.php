<?php

namespace Tests\Unit\Validate;

use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class UpdateBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private Book $book;

    /**
     * 検証に必要なジャンルと更新対象書籍を作成
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);

        $user = User::factory()->create();

        $this->book = $user->books()->create([
            'title' => '更新対象書籍',
            'author' => '更新前の著者',
            'isbn' => '9999999999999',
            'published_date' => '2026-07-01',
            'description' => '更新前の説明です。',
            'image_url' => 'https://example.com/old-book.jpg',
        ]);
    }

    /**
     * UpdateBookRequestのルールでバリデーターを作成
     */
    private function makeValidator(array $data, Book $book): ValidationValidator
    {
        $request = new UpdateBookRequest;

        // UpdateBookRequest内の$this->route('book')で更新対象書籍を取得できるようにする。
        $route = new Route(['PUT'], 'books/{book}', []);
        $route->bind($request);
        $route->setParameter('book', $book);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    /**
     * 正常な書籍入力データを作成し、必要な項目だけ上書きできるようにする
     */
    private function validData(array $override = []): array
    {
        $genre = Genre::query()->firstOrFail();

        return array_merge([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1111111111111',
            'published_date' => '2026-07-17',
            'description' => 'テスト用の書籍説明です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ], $override);
    }

    public function test_全ての項目が入力されていればバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData(),
            $this->book
        );

        $this->assertFalse($validator->fails());
    }

    public function test_説明と画像_ur_lが空でもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => null,
                'image_url' => null,
            ]),
            $this->book
        );

        $this->assertFalse($validator->fails());
    }

    public function test_必須項目が送信されていない場合はバリデーションエラーになる(): void
    {
        $requiredFields = [
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ];

        foreach ($requiredFields as $field) {
            $data = $this->validData();

            unset($data[$field]);

            $validator = $this->makeValidator(
                $data,
                $this->book
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has($field)
            );
        }
    }

    public function test_タイトルが文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'title' => ['更新後のタイトル'],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('title')
        );
    }

    public function test_著者名が文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => ['更新後の著者'],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('author')
        );
    }

    public function test_説明が文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => ['更新後の説明'],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('description')
        );
    }

    public function test_isbnが13桁でない場合はバリデーションエラーになる(): void
    {
        $invalidIsbns = [
            str_repeat('1', 12),
            str_repeat('1', 14),
        ];

        foreach ($invalidIsbns as $isbn) {
            $validator = $this->makeValidator(
                $this->validData([
                    'isbn' => $isbn,
                ]),
                $this->book
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('isbn')
            );
        }
    }

    public function test_isbnが13文字でも数字でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => 'abcdefghijklm',
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('isbn')
        );
    }

    public function test_更新対象自身のisbnはそのまま使用できる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => $this->book->isbn,
            ]),
            $this->book
        );

        $this->assertFalse($validator->fails());
    }

    public function test_他の書籍が使用しているisbnには変更できない(): void
    {
        $user = User::factory()->create();

        $otherBook = $user->books()->create([
            'title' => '別の書籍',
            'author' => '別の著者',
            'isbn' => '2222222222222',
            'published_date' => '2026-07-01',
            'description' => null,
            'image_url' => null,
        ]);

        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => $otherBook->isbn,
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('isbn')
        );
    }

    public function test_published_dateが日付形式でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'published_date' => '日付ではありません',
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('published_date')
        );
    }

    public function test_image_urlが_ur_l形式でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => 'URL形式ではありません',
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('image_url')
        );
    }

    public function test_画像_ur_lが文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => [
                    'https://example.com/book.jpg',
                ],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('image_url')
        );
    }

    public function test_存在しないジャンルはバリデーションエラーになる(): void
    {
        $notExistingGenreId =
            Genre::query()->max('id') + 1;

        $validator = $this->makeValidator(
            $this->validData([
                'genres' => [$notExistingGenreId],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres.0')
        );
    }

    public function test_ジャンル入力が配列でない場合はバリデーションエラーになる(): void
    {
        $genre = Genre::query()->firstOrFail();

        $validator = $this->makeValidator(
            $this->validData([
                'genres' => $genre->id,
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres')
        );
    }

    public function test_ジャンル_i_dが整数でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => ['整数ではありません'],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres.0')
        );
    }

    public function test_同じジャンルが重複している場合はバリデーションエラーになる(): void
    {
        $genre = Genre::query()->firstOrFail();

        $validator = $this->makeValidator(
            $this->validData([
                'genres' => [
                    $genre->id,
                    $genre->id,
                ],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres.0')
            || $validator->errors()->has('genres.1')
        );
    }

    public function test_ジャンルが1件も選択されていない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => [],
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres')
        );
    }

    public function test_タイトルが255文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'title' => str_repeat('あ', 255),
            ]),
            $this->book
        );

        $this->assertFalse($validator->fails());
    }

    public function test_タイトルが256文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'title' => str_repeat('あ', 256),
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('title')
        );
    }

    public function test_著者名が255文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => str_repeat('あ', 255),
            ]),
            $this->book
        );

        $this->assertFalse($validator->fails());
    }

    public function test_著者名が256文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => str_repeat('あ', 256),
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('author')
        );
    }

    public function test_説明が2000文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => str_repeat('あ', 2000),
            ]),
            $this->book
        );

        $this->assertFalse($validator->fails());
    }

    public function test_説明が2001文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => str_repeat('a', 2001),
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('description')
        );
    }

    public function test_画像_ur_lが512文字の場合はバリデーションを通過する(): void
    {
        $baseUrl = 'https://example.com/';

        $imageUrl = $baseUrl.str_repeat(
            'a',
            512 - strlen($baseUrl)
        );

        $this->assertSame(
            512,
            strlen($imageUrl)
        );

        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => $imageUrl,
            ]),
            $this->book
        );

        $this->assertFalse($validator->fails());
    }

    public function test_画像_ur_lが513文字の場合はバリデーションエラーになる(): void
    {
        $baseUrl = 'https://example.com/';

        $imageUrl = $baseUrl.str_repeat(
            'a',
            513 - strlen($baseUrl)
        );

        $this->assertSame(
            513,
            strlen($imageUrl)
        );

        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => $imageUrl,
            ]),
            $this->book
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('image_url')
        );
    }
}
