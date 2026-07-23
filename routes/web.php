<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;


// ゲストも閲覧可能
Route::get('/books',[BookController::class,'index'])
    ->name('books.index');

Route::get('/ranking', fn () => 'ランキング（準備中）')
        ->name('ranking.index');



// 認証済みユーザーのみ
Route::middleware('auth')->group(function () {
    Route::get('/books/create', [BookController::class,'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class,'store'])
        ->name('books.store');
    
    Route::get('/books/{book}/edit', [BookController::class,'edit'])
        ->whereNumber('book')
        ->name('books.edit');
    
    Route::put('/books/{book}', [BookController::class,'update'])
        ->whereNumber('book')
        ->name('books.update');

    Route::delete('/books/{book}', [BookController::class,'destroy'])
        ->whereNumber('book')
        ->name('books.destroy');
    
    Route::post('/books/{book}/reviews', fn () => 'レビュー作成（準備中）')
        ->whereNumber('book')
        ->name('reviews.store');
    
    Route::put('/books/{book}/reviews', fn () => 'レビュー編集（準備中）')
        ->whereNumber('book')
        ->name('reviews.edit');
    
    Route::delete('/books/{book}/reviews', fn () => 'レビュー削除（準備中）')
        ->whereNumber('book')
        ->name('reviews.destroy');
    
    Route::post('/books/{book}/reviews/like', fn () => 'レビューにいいねボタンをおす（準備中）')
        ->whereNumber('book')
        ->name('reviews.like');
    
    Route::get('/genres', fn () => 'ジャンル一覧（準備中）')
        ->name('genres.index');

    Route::get('/favorites', fn () => 'お気に入り一覧（準備中）')
        ->name('favorites.index');

    Route::post('/favorites', fn () => 'お気に入り登録（準備中）')
        ->name('favorites.toggle');
    


    
});

// 可変パラメータを持つRouteは固定Routeより後
    Route::get('/books/{book}', [BookController::class,'show'])
    ->whereNumber('book')
    ->name('books.show');

