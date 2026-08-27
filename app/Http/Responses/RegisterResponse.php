<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * 会員登録後に自動ログイン状態を解除し、ログイン画面へリダイレクトする
     *
     * @param  mixed  $request  登録完了後のリクエスト
     * @return Response ログイン画面へのリダイレクトレスポンス
     */
    public function toResponse($request): Response
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()
            ->route('login');
    }
}
