<?php

namespace Tests\Unit\Validate;

use App\Http\Requests\UpdateReadingPlanRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class UpdateReadingPlanTest extends TestCase
{
    /**
     * UpdateReadingPlanRequestのルールでバリデーターを作成する
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new UpdateReadingPlanRequest;

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages(),
        );
    }

    /**
     * 正常な読書計画更新データを作成し、必要な項目だけ上書きできるようにする
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'target_date' => now()->toDateString(),
        ], $override);
    }

    public function test_期日が正しい場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator($this->validData());

        $this->assertFalse($validator->fails());
    }

    public function test_期日が送信されていない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('target_date', $validator->errors()->toArray());
    }

    public function test_期日が日付形式でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator($this->validData([
            'target_date' => 'not-date',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('target_date', $validator->errors()->toArray());
    }
}
