<?php

namespace Tests\Unit\Validate;

use App\Http\Requests\IndexBookRequest;
use App\Models\Genre;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class IndexBookRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テストで使用するジャンルを作成
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GenreSeeder::class);
    }

    /**
     * IndexBookRequestのルールでバリデーターを作成
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new IndexBookRequest;

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    public function test_検索条件が未指定でもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([]);

        $this->assertFalse($validator->fails());
    }

    public function test_キーワード、ジャンル、ソートがnullでもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'keyword' => null,
            'genre' => null,
            'sort' => null,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_正しいキーワードでバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'keyword' => '吾輩は猫である',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_正しいジャンルを選択した場合はバリデーションを通過する(): void
    {
        $genre = Genre::query()->firstOrFail();
        $validator = $this->makeValidator([
            'genre' => $genre->id,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_正しいソートを選択した場合はバリデーションを通過する(): void
    {
        $validSorts = [
            'newest',
            'oldest',
            'rating',
            'title',
        ];

        foreach ($validSorts as $sort) {
            $validator = $this->makeValidator(
                [
                    'sort' => $sort,
                ]
            );

            $this->assertFalse($validator->fails());
        }
    }

    public function test_キーワードが文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'keyword' => ['吾輩は猫である'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'キーワードは文字列で入力してください。',
            $validator->errors()->first('keyword')
        );
    }

    public function test_ジャンルが整数でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'genre' => ['整数ではありません'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('genre')
        );
        $this->assertSame(
            'ジャンルIDは整数で指定してください。',
            $validator->errors()->first('genre')
        );
    }

    public function test_存在しないジャンルを選択した場合はバリデーションエラーになる(): void
    {
        $notExistingGenreId = Genre::query()->max('id') + 1;

        $validator = $this->makeValidator([
            'genre' => $notExistingGenreId,
        ]);

        $this->assertTrue($validator->fails());

        $this->assertTrue(
            $validator->errors()->has('genre')
        );

        $this->assertSame(
            '指定されたジャンルは存在しません。',
            $validator->errors()->first('genre')
        );
    }

    public function test_ソートが文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'sort' => ['newest'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            '並び順は文字列で指定してください。',
            $validator->errors()->first('sort')
        );
    }

    public function test_指定された並び順以外を選択した場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'sort' => 'invalid-sort',
        ]);

        $this->assertTrue($validator->fails());

        $this->assertTrue(
            $validator->errors()->has('sort')
        );

        $this->assertSame(
            '指定された並び順は使用できません。',
            $validator->errors()->first('sort')
        );
    }

    public function test_キーワードが255文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'keyword' => str_repeat('あ', 255),
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_キーワードが256文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'keyword' => str_repeat('あ', 256),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'キーワードは255文字以内で入力してください。',
            $validator->errors()->first('keyword')
        );
    }
}
