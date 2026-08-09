<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReadingPlanRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'target_date' =>['required', 'date']
        ];
    }
    /**
     * バリデーションエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を入力してください。',
            'book_id.integer' => '書籍は整数で入力してください。',
            'book_id.exists' => '選択された書籍は存在しません。',
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は日付形式で入力してください。',
        ];
    }
}
