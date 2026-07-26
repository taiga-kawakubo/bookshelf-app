<?php

namespace Tests\Unit\Api\V1\Validation;

use App\Http\Requests\Api\V1\IndexBookRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class IndexBookRequestTest extends TestCase
{
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

    public function test_正常なページ番号ではバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'page' => 1,
        ]);

        $this->assertTrue(
            $validator->passes(),
            $validator->errors()->first()
        );
    }

    public function test_正常なページあたりの件数ではバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 1,
        ]);

        $this->assertTrue(
            $validator->passes(),
            $validator->errors()->first()
        );
    }

    public function test_ページ番号とページあたりの件数がnullでもバリデーションを通過する(): void
    {
        $validator = $this->makeValidator([
            'page' => null,
            'per_page' => null,
        ]);

        $this->assertTrue(
            $validator->passes(),
            $validator->errors()->first()
        );
    }

    public function test_ページ番号が整数でない場合バリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([
            'page' => 'abc',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('page'));

        $this->assertSame(
            'ページ番号は整数で指定してください。',
            $validator->errors()->first('page')
        );
    }

    public function test_ページ番号が0の場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'page' => 0,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('page'));

        $this->assertSame(
            'ページ番号は1以上で指定してください。',
            $validator->errors()->first('page')
        );
    }

    public function test_ページあたりの件数が整数でない場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 'abc',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('per_page'));

        $this->assertSame(
            'ページあたりの件数は整数で指定してください。',
            $validator->errors()->first('per_page')
        );
    }

    public function test_ページあたりの件数が0の場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 0,
        ]);

        $this->assertFalse($validator->passes());
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

        $this->assertTrue($validator->passes());
    }

    public function test_ページあたりの件数が101の場合バリデーションエラーとなる(): void
    {
        $validator = $this->makeValidator([
            'per_page' => 101,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('per_page'));

        $this->assertSame(
            'ページあたりの件数は100以下で指定してください。',
            $validator->errors()->first('per_page')
        );
    }
}
