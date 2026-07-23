<?php

namespace Tests\Unit\Validate;

use App\Http\Requests\UpdateReviewRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class UpdateReviewRequestTest extends TestCase
{
    /**
     * UpdateReviewRequestのルールでバリデーターを作成する
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new UpdateReviewRequest();

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    /**
     * 正常なレビュー更新データを作成する
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'rating' => 3,
            'comment' => '更新後のレビュー内容です。',
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
        $requiredFields = [
            'rating',
            'comment',
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

    public function test_評価が整数でない場合はバリデーションエラーになる(): void
    {
        $invalidRatings = [
            3.5,
            '評価なし',
            [3],
        ];

        foreach ($invalidRatings as $rating) {
            $validator = $this->makeValidator(
                $this->validData([
                    'rating' => $rating,
                ])
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has('rating')
            );
        }
    }

    public function test_評価が1未満の場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'rating' => 0,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('rating')
        );
    }

    public function test_評価が5より大きい場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'rating' => 6,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('rating')
        );
    }

    public function test_評価が1の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'rating' => 1,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_評価が5の場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'rating' => 5,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_レビューが文字列でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator(
            $this->validData([
                'comment' => ['配列のレビュー'],
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('comment')
        );
    }

    public function test_レビューが1000文字の場合はバリデーションを通過する(): void
    {
        $comment = str_repeat('あ', 1000);

        $this->assertSame(
            1000,
            mb_strlen($comment)
        );

        $validator = $this->makeValidator(
            $this->validData([
                'comment' => $comment,
            ])
        );

        $this->assertFalse($validator->fails());
    }

    public function test_レビューが1001文字の場合はバリデーションエラーになる(): void
    {
        $comment = str_repeat('あ', 1001);

        $this->assertSame(
            1001,
            mb_strlen($comment)
        );

        $validator = $this->makeValidator(
            $this->validData([
                'comment' => $comment,
            ])
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('comment')
        );
    }
}