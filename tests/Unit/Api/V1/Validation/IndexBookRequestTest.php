<?php

namespace Tests\Unit\Api\V1\Validation;

use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class IndexBookRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * IndexBookRequestのルールでバリデーターを作成する。
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

    public function test_キーワードが文字列ならバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'keyword' => 'Laravel',
        ]);

        $this->assertFalse(
            $validator->fails(),
            $validator->errors()->first()
        );
    }

    public function test_存在するジャンル_idではバリデーションを通過する(): void
    {
        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $validator = $this->makeValidator([
            'genre' => $genre->id,
        ]);

        $this->assertFalse(
            $validator->fails(),
            $validator->errors()->first()
        );
    }

    public function test_正常なページ番号ではバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'page' => 1,
        ]);

        $this->assertFalse(
            $validator->fails(),
            $validator->errors()->first()
        );
    }

    public function test_正しいソートを選択した場合はバリデーションを通過する(): void
    {
        $validSorts = collect([
            'newest',
            'oldest',
            'rating',
            'title',
        ]);

        $validSorts
            ->each(function ($sort): void {
                $validator = $this->makeValidator([
                    'sort' => $sort,
                ]);

                $this->assertFalse($validator->fails());
            });
    }

    public function test_正常なページあたりの件数ではバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 1,
        ]);

        $this->assertFalse(
            $validator->fails(),
            $validator->errors()->first()
        );
    }

    public function test_ページ番号とページあたりの件数がnullでもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'page' => null,
            'per_page' => null,
        ]);

        $this->assertFalse(
            $validator->fails(),
            $validator->errors()->first()
        );
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

    public function test_ジャンルが整数でない場合はエラーになる(): void
    {
        $validator = $this->makeValidator([
            'genre' => 'abc',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('genre'));

        $this->assertSame(
            'ジャンルIDは整数で指定してください。',
            $validator->errors()->first('genre')
        );
    }

    public function test_存在しないジャンル_idの場合はエラーになる(): void
    {
        $validator = $this->makeValidator([
            'genre' => 999999,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('genre'));

        $this->assertSame(
            '指定されたジャンルは存在しません。',
            $validator->errors()->first('genre')
        );
    }

    public function test_指定外のソートはバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'sort' => 'popular',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('sort'));

        $this->assertSame(
            '指定された並び順は使用できません。',
            $validator->errors()->first('sort')
        );
    }

    public function test_ページ番号が整数でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'page' => 'abc',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('page'));

        $this->assertSame(
            'ページ番号は整数で指定してください。',
            $validator->errors()->first('page')
        );
    }

    public function test_ページあたりの件数が整数でない場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 'abc',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('per_page'));

        $this->assertSame(
            'ページあたりの件数は整数で指定してください。',
            $validator->errors()->first('per_page')
        );
    }

    public function test_キーワードが255文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'keyword' => str_repeat('あ', 255),
        ]);

        $this->assertFalse(
            $validator->fails(),
            $validator->errors()->first()
        );
    }

    public function test_キーワードが256文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'keyword' => str_repeat('あ', 256),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('keyword'));

        $this->assertSame(
            'キーワードは255文字以内で入力してください。',
            $validator->errors()->first('keyword')
        );
    }

    public function test_ページ番号が0の場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'page' => 0,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('page'));

        $this->assertSame(
            'ページ番号は1以上で指定してください。',
            $validator->errors()->first('page')
        );
    }

    public function test_ページあたりの件数が0の場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 0,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('per_page'));

        $this->assertSame(
            'ページあたりの件数は1以上で指定してください。',
            $validator->errors()->first('per_page')
        );
    }

    public function test_ページあたりの件数が100ではバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 100,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_ページあたりの件数が101の場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 101,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('per_page'));

        $this->assertSame(
            'ページあたりの件数は100以下で指定してください。',
            $validator->errors()->first('per_page')
        );
    }
}
