<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 未認証ユーザーも閲覧可能
|--------------------------------------------------------------------------
*/

// 書籍一覧画面の表示
Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

// ランキング画面の表示
Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

/*
|--------------------------------------------------------------------------
| 認証ユーザーのみ
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // 書籍登録画面の表示
    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    // 書籍の登録
    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    // 書籍編集画面の表示
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->whereNumber('book')
        ->name('books.edit');

    // 書籍の更新
    Route::put('/books/{book}', [BookController::class, 'update'])
        ->whereNumber('book')
        ->name('books.update');

    // 書籍の削除
    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->whereNumber('book')
        ->name('books.destroy');

    // レビューの登録
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->whereNumber('book')
        ->name('reviews.store');

    // レビュー編集画面の表示
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->whereNumber('review')
        ->name('reviews.edit');

    // レビューの更新
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->whereNumber('review')
        ->name('reviews.update');

    // レビューの削除
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->whereNumber('review')
        ->name('reviews.destroy');

    // レビューの「いいね」登録・解除
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
        ->whereNumber('review')
        ->name('reviews.like');

    // ジャンル一覧画面の表示
    Route::get('/genres', [GenreController::class, 'index'])
        ->name('genres.index');

    // ジャンル登録画面の表示
    Route::get('/genres/create', [GenreController::class, 'create'])
        ->name('genres.create');

    // ジャンル詳細画面の表示
    Route::get('/genres/{genre}', [GenreController::class, 'show'])
        ->whereNumber('genre')
        ->name('genres.show');

    // ジャンルの登録
    Route::post('/genres', [GenreController::class, 'store'])
        ->name('genres.store');

    // ジャンル編集画面の表示
    Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])
        ->whereNumber('genre')
        ->name('genres.edit');

    // ジャンルの更新
    Route::put('/genres/{genre}', [GenreController::class, 'update'])
        ->whereNumber('genre')
        ->name('genres.update');

    // ジャンルの削除
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])
        ->whereNumber('genre')
        ->name('genres.destroy');

    // お気に入り書籍の一覧を表示
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    // お気に入り書籍の登録・解除
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->whereNumber('book')
        ->name('favorites.toggle');

    //マイレポート
    Route::get('/reports',fn() => 'レポート画面（準備中)')
        ->name('reports.index');
    
    //読書計画一覧画面の表示
    Route::get('/reading-plans',fn() => '読書計画画面（準備中)')
        ->name('reading-plans.index');

    ////通知一覧の表示
    Route::get('/notifications',fn() => '通知一覧画面（準備中)')
        ->name('notifications.index');

});

/*
|--------------------------------------------------------------------------
| 可変パラメータを持つRouteは固定Routeより後に定義
|--------------------------------------------------------------------------
*/

// 書籍詳細画面の表示
Route::get('/books/{book}', [BookController::class, 'show'])
    ->whereNumber('book')
    ->name('books.show');
