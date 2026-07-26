<?php

namespace App\Exceptions;

use App\Models\Book;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class Handler extends ExceptionHandler
{
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
    }

    protected function invalidJson($request,ValidationException $exception){
        if ($request->is('api/*')) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors' => $exception->errors(),
            ], $exception->status);
        }
        return parent::invalidJson($request, $exception);
    }
}
