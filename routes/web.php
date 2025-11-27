<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Home Routes
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');

Route::get('/welcome', function () {
    return Inertia::render('Onboarding');
})->name('welcome');

Route::get('/server-time', function () {
    return response()->json([
        'timestamp' => now()->getTimestamp() * 1000,
        'timezone' => config('app.timezone'),
        'server_time' => now()->toISOString()
    ]);
})->name('server-time');

// storage link
Route::get('/link-storage', function () {
    Artisan::call('storage:link');
    return 'Storage linked successfully';
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/user.php';
