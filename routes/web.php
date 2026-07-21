<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/books');

// ゲストも閲覧可能
Route::get('/books', fn () => '書籍一覧（準備中）')
    ->name('books.index');

// 認証済みユーザーのみ
Route::middleware('auth')->group(function () {
    Route::get('/books/create', fn () => '書籍登録（準備中）')
        ->name('books.create');

    Route::get('/genres', fn () => 'ジャンル一覧（準備中）')
        ->name('genres.index');
});

// 可変パラメータを持つRouteは固定Routeより後
Route::get('/books/{book}', fn (int $book) => "書籍詳細：{$book}（準備中）")
    ->whereNumber('book')
    ->name('books.show');