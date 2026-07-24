<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use Illuminate\Support\Facades\Route;

// ゲストも閲覧可能
Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/ranking', fn () => 'ランキング（準備中）')
    ->name('ranking.index');

// 認証済みユーザーのみ
Route::middleware('auth')->group(function () {
    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->whereNumber('book')
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->whereNumber('book')
        ->name('books.update');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->whereNumber('book')
        ->name('books.destroy');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->whereNumber('book')
        ->name('reviews.store');

    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->whereNumber('review')
        ->name('reviews.edit');

    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->whereNumber('review')
        ->name('reviews.update');

    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->whereNumber('review')
        ->name('reviews.destroy');

    Route::post('/books/{book}/reviews/like', fn () => 'レビューにいいねボタンをおす（準備中）')
        ->whereNumber('book')
        ->name('reviews.like');

    Route::get('/genres', [GenreController::class, 'index'])
        ->name('genres.index');

    Route::get('/genres/create', [GenreController::class, 'create'])
        ->name('genres.create');

    Route::get('/genres/{genre}', [GenreController::class, 'show'])
        ->name('genres.show');

    Route::post('/genres', [GenreController::class, 'store'])
        ->name('genres.store');

    Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])
        ->name('genres.edit');

    Route::put('/genres/{genre}', [GenreController::class, 'update'])
        ->name('genres.update');

    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])
        ->name('genres.destroy');

    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    Route::post('/books/{book}/favorites',  [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

});

// 可変パラメータを持つRouteは固定Routeより後
Route::get('/books/{book}', [BookController::class, 'show'])
    ->whereNumber('book')
    ->name('books.show');
