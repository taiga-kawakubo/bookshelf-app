<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'integer', 'exists:genres,id'],
            'sort' => ['nullable', 'string', Rule::in([
                'newest',
                'oldest',
                'rating',
                'title',
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'genre.integer' => 'ジャンルIDは整数で指定してください。',
            'genre.exists' => '指定されたジャンルは存在しません。',
            'sort.string' => '並び順は文字列で指定してください。',
            'sort.in' => '指定された並び順は使用できません。',
            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',
            'per_page.integer' => 'ページあたりの件数は整数で指定してください。',
            'per_page.min' => 'ページあたりの件数は1以上で指定してください。',
            'per_page.max' => 'ページあたりの件数は100以下で指定してください。',
        ];
    }
}
