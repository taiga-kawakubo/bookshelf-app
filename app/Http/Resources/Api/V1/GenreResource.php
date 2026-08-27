<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenreResource extends JsonResource
{
    /**
     * ジャンル情報をAPIレスポンス用の配列に変換する
     *
     * @param  Request  $request  APIリクエスト
     * @return array<string, mixed> ジャンルのレスポンスデータ
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
