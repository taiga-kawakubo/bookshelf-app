<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'url', 'max:512'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'distinct', 'exists:genres,id'],
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
            'title.required' => 'タイトルを入力してください。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者を入力してください。',
            'author.string' => '著者は文字列で入力してください。',
            'author.max' => '著者は255文字以内で入力してください。',

            'isbn.required' => 'ISBNを入力してください。',
            'isbn.digits' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => '入力されたISBNはすでに使用されています。',

            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '出版日は有効な日付で入力してください。',

            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は2000文字以内で入力してください。',

            'image_url.string' => '画像URLは文字列で入力してください。',
            'image_url.url' => '画像URLは正しいURL形式で入力してください。',
            'image_url.max' => '画像URLは512文字以内で入力してください。',

            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.array' => 'ジャンルは配列形式で送信してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',

            'genres.*.integer' => 'ジャンルIDは整数で指定してください。',
            'genres.*.distinct' => 'ジャンルは重複せずに選択してください。',
            'genres.*.exists' => '選択されたジャンルは存在しません。',
        ];
    }
}
