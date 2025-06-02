<?php

use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong', 'timestamp' => now()->toIso8601String()]);
});

Route::prefix('v1/image')->group(function () {

    Route::get('/{id}', [ImageController::class, 'index']);
});
