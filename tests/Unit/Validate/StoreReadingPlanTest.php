<?php

namespace Tests\Unit\Validate;

use App\Http\Requests\StoreReadingPlanRequest;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class StoreReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * StoreReadingPlanRequestのルールでバリデーターを作成する
     */
    private function makeValidator(array $data): ValidationValidator
    {
        $request = new StoreReadingPlanRequest;

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages(),
        );
    }

    /**
     * 正常な読書計画入力データを作成し、必要な項目だけ上書きできるようにする
     */
    private function validData(array $override = []): array
    {
        $book = Book::factory()->create();

        return array_merge([
            'book_id' => $book->id,
            'target_date' => now()->toDateString(),
        ], $override);
    }

    public function test_全ての項目が正しい場合はバリデーションを通過する(): void
    {
        $validator = $this->makeValidator($this->validData());

        $this->assertFalse($validator->fails());
    }

    public function test_必須項目が送信されていない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('book_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('target_date', $validator->errors()->toArray());
    }

    public function test_書籍_i_dが整数でない場合はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator($this->validData([
            'book_id' => 'abc',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('book_id', $validator->errors()->toArray());
    }

    public function test_存在しない書籍はバリデーションエラーになる(): void
    {
        $validator = $this->makeValidator($this->validData([
            'book_id' => 999999,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('book_id', $validator->errors()->toArray());
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
