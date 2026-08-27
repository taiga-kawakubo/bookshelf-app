<?php

namespace App\Exceptions;

use App\Models\Book;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * APIリクエスト向けの例外レスポンスを登録する
     */
    public function register(): void
    {
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previousException = $e->getPrevious();

            if (
                $previousException instanceof ModelNotFoundException
                && $previousException->getModel() === Book::class
            ) {
                return response()->json([
                    'message' => '対象の書籍が見つかりませんでした。',
                ], 404);
            }

            return response()->json([
                'message' => 'エンドポイントが見つかりません。',
            ], 404);
        });

        $this->renderable(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => '認証が必要です。',
            ], 401);
        });

        $this->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'この操作を行う権限がありません。',
            ], 403);
        });
    }

    /**
     * バリデーションエラー時のJSONレスポンスを返す
     *
     * @param  mixed  $request  バリデーションエラーが発生したリクエスト
     * @param  ValidationException  $exception  バリデーション例外
     * @return mixed JSONレスポンスまたは親クラスのレスポンス
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors' => $exception->errors(),
            ], $exception->status);
        }

        return parent::invalidJson($request, $exception);
    }
}
