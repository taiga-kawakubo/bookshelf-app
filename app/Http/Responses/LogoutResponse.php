<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * ログアウト後にログイン画面へリダイレクトする
     *
     * @param  mixed  $request  ログアウト完了後のリクエスト
     * @return Response ログイン画面へのリダイレクトレスポンス
     */
    public function toResponse($request): Response
    {
        return redirect()
            ->route('login');
    }
}
