<?php

use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/image')->group(function () {

    Route::get('/', [ImageController::class, 'index']);
});
