<?php

namespace Tests\Unit\Api\V1\Validation;

use App\Http\Requests\Api\V1\UpdateBookRequest;
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

    private User $user;

    private Book $book;

    private Genre $genre;

    /**
     * 検証に必要なユーザー、ジャンル、更新対象書籍を作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);

        $this->user = User::factory()->create();

        $this->book = Book::query()->create([
            'user_id' => $this->user->id,
            'title' => '更新前の書籍',
            'author' => '更新前の著者',
            'isbn' => '1111111111111',
            'published_date' => '2026-07-01',
            'description' => '更新前の説明です。',
            'image_url' => 'https://example.com/before.jpg',
        ]);

        $this->genre = Genre::query()->firstOrFail();
    }

    /**
     * UpdateBookRequestのルールでバリデーターを作成する。
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new UpdateBookRequest;

        // UpdateBookRequest内の$this->route('book')が更新対象のBookモデルを取得できるようにする。
        $route = new Route(['PUT'], 'api/v1/books/{book}', []);

        // Routeのパラメータ配列を初期化する
        $route->bind($request);
        // bookパラメータを更新対象Bookモデルへ置き換える
        $route->setParameter('book', $this->book);

        $request->setRouteResolver(fn () => $route);

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    /**
     * 正常な書籍更新データを作成し、必要な項目だけ上書きできるようにする。
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => $this->book->isbn,
            'published_date' => '2026-07-27',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/after.jpg',
            'genres' => [$this->genre->id],
        ], $override);
    }

    public function test_全ての項目が正しい場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_説明と画像_ur_lがnullでもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => null,
                'image_url' => null,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_更新対象自身の_isb_nはそのまま使用できる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => $this->book->isbn,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_user_idはバリデーション済みデータに含まれない(): void
    {
        $anotherUser = User::factory()->create();

        $validator = $this->makeValidator(
            $this->validData([
                'user_id' => $anotherUser->id,
            ])
        );

        $this->assertFalse($validator->fails());

        $this->assertArrayNotHasKey(
            'user_id',
            $validator->validated()
        );
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
                'title' => ['更新後の書籍'],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('title')
        );
    }

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

    public function test_著者が文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => ['更新後の著者'],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('author')
        );
    }

    public function test_著者が255文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => str_repeat('あ', 255),
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_著者が256文字の場合はバリデーションエラーになる(): void
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

    public function test_isb_nが13桁でない場合はバリデーションエラーになる(): void
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

    public function test_isb_nが13文字でも数字でない場合はバリデーションエラーになる(): void
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

    public function test_別の書籍が使用している_isb_nには変更できない(): void
    {
        $otherBook = Book::query()->create([
            'user_id' => $this->user->id,
            'title' => '別の書籍',
            'author' => '別の著者',
            'isbn' => '2222222222222',
            'published_date' => '2026-07-02',
            'description' => null,
            'image_url' => null,
        ]);

        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => $otherBook->isbn,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('isbn')
        );
    }

    public function test_出版日が日付形式でない場合はバリデーションエラーになる(): void
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

    public function test_説明が文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => ['更新後の説明'],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('description')
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
                'description' => str_repeat('あ', 2001),
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('description')
        );
    }

    public function test_画像_ur_lが文字列でない場合はバリデーションエラーになる(): void
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

    public function test_画像_ur_lが_ur_l形式でない場合はバリデーションエラーになる(): void
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

    public function test_画像_ur_lが512文字の場合はバリデーションを通過する(): void
    {
        $baseUrl = 'https://example.com/';

        $imageUrl = $baseUrl.str_repeat('a', 512 - strlen($baseUrl));

        $this->assertSame(512, strlen($imageUrl));

        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => $imageUrl,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_画像_ur_lが513文字の場合はバリデーションエラーになる(): void
    {
        $baseUrl = 'https://example.com/';

        $imageUrl = $baseUrl.str_repeat('a', 513 - strlen($baseUrl));

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

    public function test_ジャンルが配列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => $this->genre->id,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genres')
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

    public function test_ジャンル_i_dが整数でない場合はバリデーションエラーになる(): void
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

    public function test_同じジャンル_i_dが重複している場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => [
                    $this->genre->id,
                    $this->genre->id,
                ],
            ])
        );

        $this->assertTrue($validator->fails());

        $this->assertTrue(
            $validator->errors()->has('genres.0')
            || $validator->errors()->has('genres.1')
        );
    }

    public function test_存在しないジャンル_i_dの場合はバリデーションエラーになる(): void
    {
        $notExistingGenreId =
            (Genre::query()->max('id') ?? 0) + 1;

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
}
