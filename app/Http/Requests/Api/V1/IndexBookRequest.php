<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',

            'per_page.integer' => 'ページあたりの件数は整数で指定してください。',
            'per_page.min' => 'ページあたりの件数は1以上で指定してください。',
            'per_page.max' => 'ページあたりの件数は100以下で指定してください。',
        ];
    }
}
