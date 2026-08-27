<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * レビュー情報をAPIレスポンス用の配列に変換する
     *
     * @param  Request  $request  APIリクエスト
     * @return array<string, mixed> レビューのレスポンスデータ
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
            'likes_count' => $this->whenHas(
                'likes_count',
                fn (): int => (int) $this->likes_count
            ),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
        ];
    }
}
