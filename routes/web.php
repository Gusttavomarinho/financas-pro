<?php

use Illuminate\Support\Facades\Route;

// SPA catch-all route - all routes are handled by Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
