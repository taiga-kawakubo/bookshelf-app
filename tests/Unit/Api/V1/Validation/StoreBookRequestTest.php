<?php

namespace Tests\Unit\Api\V1\Validation;

use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class StoreBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * 検証に必要なユーザーとジャンルを作成する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);

        $this->user = User::factory()->create();
    }

    /**
     * StoreBookRequestのルールでバリデーターを作成する。
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new StoreBookRequest;

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    /**
     * 正常な書籍登録データを作成し、指定された値で上書きする。
     */
    private function validData(array $override = []): array
    {
        $genre = Genre::query()->firstOrFail();

        return array_merge([
            'user_id' => $this->user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1111111111111',
            'published_date' => '2026-07-17',
            'description' => 'テスト用の書籍説明です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ], $override);
    }

    public function test_全ての項目が正常ならバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData()
        );

        $this->assertTrue(
            $validator->passes(),
            $validator->errors()->first()
        );
    }

    public function test_説明と画像urlがnullでもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => null,
                'image_url' => null,
            ])
        );

        $this->assertTrue(
            $validator->passes(),
            $validator->errors()->first()
        );
    }

    public function test_登録者が未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['user_id']);

        $validator = $this->makeValidator($data);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('user_id'));

        $this->assertSame(
            '登録者IDを指定してください。',
            $validator->errors()->first('user_id')
        );
    }

    public function test_登録者が整数でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'user_id' => 'abc',
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('user_id'));

        $this->assertSame(
            '登録者IDは整数で指定してください。',
            $validator->errors()->first('user_id')
        );
    }

    public function test_存在しない登録者の場合バリデーションエラーになる(): void
    {
        $missingUserId = (User::query()->max('id') ?? 0) + 1;

        $validator = $this->makeValidator(
            $this->validData([
                'user_id' => $missingUserId,
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('user_id'));

        $this->assertSame(
            '指定された登録者は存在しません。',
            $validator->errors()->first('user_id')
        );
    }

    public function test_タイトルが未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['title']);

        $validator = $this->makeValidator($data);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title'));

        $this->assertSame(
            'タイトルを入力してください。',
            $validator->errors()->first('title')
        );
    }

    public function test_タイトルが文字列でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'title' => ['テスト書籍'],
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title'));

        $this->assertSame(
            'タイトルは文字列で入力してください。',
            $validator->errors()->first('title')
        );
    }

    public function test_タイトルが255文字を超える場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'title' => str_repeat('あ', 256),
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('title'));

        $this->assertSame(
            'タイトルは255文字以内で入力してください。',
            $validator->errors()->first('title')
        );
    }

    public function test_著者が未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['author']);

        $validator = $this->makeValidator($data);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('author'));

        $this->assertSame(
            '著者を入力してください。',
            $validator->errors()->first('author')
        );
    }

    public function test_著者が文字列でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => ['テスト著者'],
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('author'));

        $this->assertSame(
            '著者は文字列で入力してください。',
            $validator->errors()->first('author')
        );
    }

    public function test_著者が255文字を超える場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'author' => str_repeat('あ', 256),
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('author'));

        $this->assertSame(
            '著者は255文字以内で入力してください。',
            $validator->errors()->first('author')
        );
    }

    public function test_isbnが未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['isbn']);

        $validator = $this->makeValidator($data);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('isbn'));

        $this->assertSame(
            'ISBNを入力してください。',
            $validator->errors()->first('isbn')
        );
    }

    public function test_isbnが13桁でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => '111111111111',
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('isbn'));

        $this->assertSame(
            'ISBNは13桁で入力してください。',
            $validator->errors()->first('isbn')
        );
    }

    public function test_isbnが既に使用されている場合バリデーションエラーになる(): void
    {
        Book::query()->create([
            'user_id' => $this->user->id,
            'title' => '登録済み書籍',
            'author' => '登録済み著者',
            'isbn' => '1111111111111',
            'published_date' => '2026-07-01',
            'description' => null,
            'image_url' => null,
        ]);

        $validator = $this->makeValidator(
            $this->validData([
                'isbn' => '1111111111111',
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('isbn'));

        $this->assertSame(
            '入力されたISBNはすでに使用されています。',
            $validator->errors()->first('isbn')
        );
    }

    public function test_出版日が未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['published_date']);

        $validator = $this->makeValidator($data);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('published_date'));

        $this->assertSame(
            '出版日を入力してください。',
            $validator->errors()->first('published_date')
        );
    }

    public function test_出版日が有効な日付でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'published_date' => '日付ではない値',
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('published_date'));

        $this->assertSame(
            '出版日は有効な日付で入力してください。',
            $validator->errors()->first('published_date')
        );
    }

    public function test_説明が文字列でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => ['説明'],
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('description'));

        $this->assertSame(
            '説明は文字列で入力してください。',
            $validator->errors()->first('description')
        );
    }

    public function test_説明が2000文字を超える場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'description' => str_repeat('あ', 2001),
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('description'));

        $this->assertSame(
            '説明は2000文字以内で入力してください。',
            $validator->errors()->first('description')
        );
    }

    public function test_画像urlが文字列でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => ['https://example.com/book.jpg'],
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('image_url'));

        $this->assertSame(
            '画像URLは文字列で入力してください。',
            $validator->errors()->first('image_url')
        );
    }

    public function test_画像urlがurl形式でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => 'URLではない値',
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('image_url'));

        $this->assertSame(
            '画像URLは正しいURL形式で入力してください。',
            $validator->errors()->first('image_url')
        );
    }

    public function test_画像urlが512文字を超える場合バリデーションエラーになる(): void
    {
        $longUrl = 'https://example.com/'.str_repeat('a', 493);

        $validator = $this->makeValidator(
            $this->validData([
                'image_url' => $longUrl,
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('image_url'));

        $this->assertSame(
            '画像URLは512文字以内で入力してください。',
            $validator->errors()->first('image_url')
        );
    }

    public function test_ジャンルが未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['genres']);

        $validator = $this->makeValidator($data);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('genres'));

        $this->assertSame(
            'ジャンルを1つ以上選択してください。',
            $validator->errors()->first('genres')
        );
    }

    public function test_ジャンルが配列でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => 1,
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('genres'));

        $this->assertSame(
            'ジャンルは配列形式で送信してください。',
            $validator->errors()->first('genres')
        );
    }

    public function test_ジャンルが空配列の場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => [],
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('genres'));

        $this->assertSame(
            'ジャンルを1つ以上選択してください。',
            $validator->errors()->first('genres')
        );
    }

    public function test_ジャンルが整数でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'genres' => ['abc'],
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('genres.0'));

        $this->assertSame(
            'ジャンルIDは整数で指定してください。',
            $validator->errors()->first('genres.0')
        );
    }

    public function test_ジャンルが重複している場合バリデーションエラーになる(): void
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

        $this->assertFalse($validator->passes());

        $hasDuplicateError =
            $validator->errors()->has('genres.0')
            || $validator->errors()->has('genres.1');

        $this->assertTrue($hasDuplicateError);

        $messages = array_merge(
            $validator->errors()->get('genres.0'),
            $validator->errors()->get('genres.1')
        );

        $this->assertContains(
            'ジャンルは重複せずに選択してください。',
            $messages
        );
    }

    public function test_存在しないジャンルの場合バリデーションエラーになる(): void
    {
        $missingGenreId = (Genre::query()->max('id') ?? 0) + 1;

        $validator = $this->makeValidator(
            $this->validData([
                'genres' => [$missingGenreId],
            ])
        );

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('genres.0'));

        $this->assertSame(
            '選択されたジャンルは存在しません。',
            $validator->errors()->first('genres.0')
        );
    }
}
