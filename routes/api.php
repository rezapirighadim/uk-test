<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(ApiTokenMiddleware::class)->group(function () {
    // Bookmark routes
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks', [BookmarkController::class, 'store']);
    Route::get('/bookmarks/{bookmark}', [BookmarkController::class, 'show']);
    Route::delete('/bookmarks/{bookmark}', [BookmarkController::class, 'destroy']);
    Route::post('/bookmarks/{bookmark}/retry', [BookmarkController::class, 'retry']);
});
