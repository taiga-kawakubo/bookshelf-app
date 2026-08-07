<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class IsbnLookupController extends Controller
{
    /**
     * ISBNから書籍情報を取得
     */
    public function show(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 422);
        }
        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => 'isbn:'.$isbn,
            'key' => config('services.google_books.api_key'),
            'maxResults' => 1,
        ]);

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
