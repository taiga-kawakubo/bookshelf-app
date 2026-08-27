<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * ログイン後に書籍一覧画面へリダイレクトする
     *
     * @param  mixed  $request  ログイン完了後のリクエスト
     * @return Response 書籍一覧画面へのリダイレクトレスポンス
     */
    public function toResponse($request): Response
    {
        return redirect()
            ->intended('/books')
            ->with('success', 'ログインしました。');
    }
}
