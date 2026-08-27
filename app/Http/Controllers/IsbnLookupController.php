<?php

namespace App\Http\Controllers;

use App\Http\Requests\IsbnLookupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class IsbnLookupController extends Controller
{
    /**
     * ISBNからGoogle Books APIを使って書籍情報を取得する
     *
     * @param  IsbnLookupRequest  $request  ISBN検索リクエスト
     * @return JsonResponse 書籍情報またはエラーメッセージのJSONレスポンス
     */
    public function show(IsbnLookupRequest $request): JsonResponse
    {
        $isbn = $request->validated('isbn');

        $response = Http::get(
            config('services.google_books.api_url'),
            [
                'q' => 'isbn:'.$isbn,
                'key' => config('services.google_books.api_key'),
                'maxResults' => 1,
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 502);
        }

        $data = $response->json();

        $items = $data['items'] ?? [];

        if (empty($items)) {
            return response()->json([
                'error' => '書籍情報が見つかりませんでした。',
            ], 404);
        }

        $volumeInfo = $items[0]['volumeInfo'] ?? [];

        $authors = $volumeInfo['authors'] ?? [];

        return response()->json([
            'title' => $volumeInfo['title'] ?? '',
            'author' => implode(', ', $authors),
            'isbn' => $isbn,
            'published_date' => $volumeInfo['publishedDate'] ?? null,
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
        ]);
    }
}
