<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('books', [BookController::class, 'index'])
            ->name('books.index');

        Route::get('books/{book}', [BookController::class, 'show'])
            ->name('books.show');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('books', [BookController::class, 'store'])
                ->name('books.store');

            Route::put('books/{book}', [BookController::class, 'update'])
                ->middleware('can:update,book')
                ->name('books.update');

            Route::delete('books/{book}', [BookController::class, 'destroy'])
                ->middleware('can:delete,book')
                ->name('books.destroy');
        });
    });
