<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IsbnLookupRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する
     *
     * @return bool 常に許可
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ルートパラメータのisbnをバリデーション対象に含める
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => $this->route('isbn'),
        ]);
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'isbn' => [
                'required',
                'digits:13',
            ],
        ];
    }

    /**
     * バリデーションメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isbn.required' => 'ISBNを13桁で入力してください。',
            'isbn.digits' => 'ISBNは13桁で入力してください。',
        ];
    }

    /**
     * バリデーション失敗時にJSON形式のエラーレスポンスを返す。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => $validator->errors()->first('isbn'),
            ], 422)
        );
    }
}
