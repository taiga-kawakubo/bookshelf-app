<?php

namespace Tests\Unit\Validate;

use App\Http\Requests\StoreGenreRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class StoreGenreRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * StoreGenreRequestのルールでバリデーターを作成する
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new StoreGenreRequest;

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    /**
     * 正常なジャンル入力データを作成し、必要な項目だけ上書きできるようにする
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'name' => 'ミステリー',
        ], $override);
    }

    public function test_全ての項目が正しい場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_必須項目が送信されていない場合はバリデーションエラーになる(): void
    {
        $data = $this->validData();

        unset($data['name']);

        $validator = $this->makeValidator($data);

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }

    public function test_ジャンル名が文字列でない場合はバリデーションエラーになる(): void
    {
        $invalidNames = [
            ['ミステリー'],
            123,
        ];

        foreach ($invalidNames as $name) {
            $validator = $this->makeValidator(
                $this->validData([
                    'name' => $name,
                ])
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('name')
            );
        }
    }

    public function test_ジャンル名が50文字の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'name' => str_repeat('あ', 50),
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_ジャンル名が51文字の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'name' => str_repeat('あ', 51),
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }

    public function test_登録済みのジャンル名の場合はバリデーションエラーになる(): void
    {
        DB::table('genres')->insert([
            'name' => 'ミステリー',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $validator = $this->makeValidator(
            $this->validData([
                'name' => 'ミステリー',
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('name')
        );
    }
}
