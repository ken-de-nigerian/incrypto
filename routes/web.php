<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Home Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['redirect.authenticated', 'guest'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('Home');
    })->name('home');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/user.php';
