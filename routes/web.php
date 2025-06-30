<?php

use Illuminate\Support\Facades\Route;


if (env('APP_ENV') != 'production') {
    Route::get('/teste', function () {
        return view('teste');
    });
}
