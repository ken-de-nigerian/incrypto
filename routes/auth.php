<?php

use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\SocialLoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['guest', 'redirect.authenticated'])->group(function () {
    // Login Routes
    Route::controller(LoginController::class)->group(function () {
        Route::get('/login', 'index')->name('login');
        Route::post('/login', 'login')->name('login.store');
    });

    // Registration Routes
    Route::controller(RegisterController::class)->group(function () {
        Route::get('/register', 'index')->name('register');
        Route::post('/register', 'register')->name('register.store');
    });

    // Verification Routes
    Route::controller(VerificationController::class)->group(function () {
        Route::get('/verify', 'verify')->name('verify');
        Route::get('/verify/resend', 'resend')->name('verify.resend');
        Route::post('/verify/store', 'store')->name('verify.store');
    });

    // Onboarding Routes
    Route::controller(OnboardingController::class)->group(function () {
        Route::get('/onboarding', 'index')->name('onboarding');
        Route::post('/onboarding/store', 'store')->name('onboarding.store');
    });

    // Password Reset Routes
    Route::controller(ForgotPasswordController::class)->group(function () {
        Route::get('/password/reset', 'create')->name('password.request');
        Route::post('/password/email', 'store')->name('password.email');
        Route::get('/password/reset/{token}', 'edit')->name('password.reset');
        Route::post('/password/update', 'update')->name('password.update');
    });

    // Social Login
    Route::controller(SocialLoginController::class)->group(function () {
        Route::get('social/{provider}', 'redirectToProvider')->name('social.redirect');
        Route::get('social/callback/{provider}', 'handleProviderCallback')->name('social.callback');
    });
});


/*
|--------------------------------------------------------------------------
| Session & Logout Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'auth.session'])
    ->controller(SessionController::class)
    ->group(function () {
        // Standard user logout
        Route::post('/logout', 'destroy')->name('logout');

        // Securely log out all other browser sessions
        Route::post('/logout/other-devices', 'logoutOtherDevices')->name('logout.other-devices');

        // Unlink a social media account
        Route::post('/social/{provider}/disconnect', 'unlinkSocialAccount')->name('social.disconnect');
    });

