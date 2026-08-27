<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * ログインユーザーがお気に入り登録した書籍一覧を表示する
     *
     * @param  Request  $request  お気に入り一覧を表示するリクエスト
     * @return View お気に入り書籍一覧画面
     */
    public function index(Request $request): View
    {
        $books = $request
            ->user()
            ->favoriteBooks()
            ->with('genres')
            ->orderBy('books.id')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * ログインユーザーの書籍お気に入り状態を登録または解除する
     *
     * @param  Request  $request  お気に入り操作を行うリクエスト
     * @param  Book  $book  お気に入り対象の書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $result = $request
            ->user()
            ->favoriteBooks()
            ->toggle($book->id);
        $message = count($result['attached']) > 0
            ? 'お気に入りに追加しました。'
            : 'お気に入りを解除しました。';

        return redirect()
            ->route('books.show', $book)
            ->with('success', $message);
    }
}
