<?php

namespace Tests\Unit\Validate;

use Tests\TestCase;
use App\Http\Requests\StoreBookRequest;
use App\Models\Genre;
use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Validator as ValidationValidator;

class StoreBookRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 検証に必要なジャンルを作成
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);
    }

    /**
     * StoreBookRequestのルールでバリデーターを作成
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new StoreBookRequest();

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
            $this->validData()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_説明と画像URLが空でもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => null,
                'image_url' => null,
            ])
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

            $validator = $this->makeValidator($data);

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
                'title' => ['テスト書籍'],
            ])
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
                'author' => ['テスト著者'],
            ])
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
                'description' => ['テスト用の書籍説明です。'],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('description')
        );
    }


    public function test_isbnが重複している場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $book = $user->books()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1111111111111',
            'published_date' => '2026-07-17',
            'description' => 'テスト用の書籍説明です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => $book->isbn,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('isbn')
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
                ])
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
            ])
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
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('published_date')
        );
    }

    public function test_image_urlがURL形式でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => 'URL形式ではありません',
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('image_url')
        );
    }

    public function test_画像URLが文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => ['https://example.com/book.jpg'],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('image_url')
        );
    }

    public function test_存在しないジャンルはバリデーションエラーになる(): void
    {
        $notExistingGenreId = Genre::query()->max('id') + 1;

        $validator = $this->makeValidator(
            $this->validData([
                'genres' => [$notExistingGenreId],
            ])
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
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres')
        );
    }

    public function test_ジャンルIDが整数でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => ['整数ではありません'],
            ])
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
            ])
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
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres')
        );
    }

    // 境界値
    public function test_タイトルが255文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'title' => str_repeat('あ', 255),
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_タイトルが256文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'title' => str_repeat('あ', 256),
            ])
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
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_著者名が256文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => str_repeat('あ', 256),
            ])
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
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_説明が2001文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => str_repeat('a', 2001),
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('description')
        );
    }

    public function test_画像URLが512文字の場合はバリデーションを通過する(): void
    {
        $baseUrl = 'https://example.com/';
        $imageUrl = $baseUrl . str_repeat(
            'a',
            512 - strlen($baseUrl)
        );

        $this->assertSame(512, strlen($imageUrl));

        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => $imageUrl,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_画像URLが513文字の場合はバリデーションエラーになる(): void
    {
        $baseUrl = 'https://example.com/';
        $imageUrl = $baseUrl . str_repeat(
            'a',
            513 - strlen($baseUrl)
        );

        $this->assertSame(513, strlen($imageUrl));

        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => $imageUrl,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('image_url')
        );
    }
}