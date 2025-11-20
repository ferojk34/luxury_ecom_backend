<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\CategoryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Start of Backend::

// CATEGORY MANAGEMENT:
Route::prefix('backend')
    ->as('backend.')
    ->group(function () {
        Route::prefix('category')->as('category.')->group(function () {
            Route::get('/list', [CategoryController::class, 'index'])
                ->name('list');

            Route::get('/detail/{id}', [CategoryController::class, 'show'])
                ->name('detail');

            Route::get('/edit/{id}', [CategoryController::class, 'edit'])
                ->name('edit');

            Route::post('/store', [CategoryController::class, 'store'])
                ->name('store');

            Route::post('/update/{id}', [CategoryController::class, 'update'])
                ->name('update');

            Route::delete('/delete/{id}', [CategoryController::class, 'destroy'])
                ->name('delete');
        });
    });

// END OF BACKEND::




