<?php

use App\Http\Controllers\User\ManageUserKycController;
use App\Http\Controllers\User\ManageUserNotificationsController;
use App\Http\Controllers\User\ManageUserProfileController;
use App\Http\Controllers\User\ManageUserReceiveCryptoController;
use App\Http\Controllers\User\ManageUserRewardsController;
use App\Http\Controllers\User\ManageUserSendCryptoController;
use App\Http\Controllers\User\ManageUserSwapCryptoController;
use App\Http\Controllers\User\ManageUserTransactionController;
use App\Http\Controllers\User\ManageUserWalletConnectController;
use App\Http\Controllers\User\UserDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::prefix('user')
    ->name('user.')
    ->middleware(['auth', 'auth.session', 'no.seedphrase', 'can:access-user-dashboard'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', UserDashboardController::class)->name('dashboard');

        // Profile Management
        Route::prefix('profile')
            ->name('profile.')
            ->controller(ManageUserProfileController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/update/profile', 'updateProfile')->name('update.profile');
                Route::put('/reset/password', 'resetPassword')->name('reset.password');
                Route::put('/update/trading/status', 'updateTradingStatus')->name('update.trading.status');
                Route::delete('/delete/profile', 'destroy')->name('destroy');
            });

        // Kyc Management
        Route::prefix('kyc')
            ->name('kyc.')
            ->controller(ManageUserKycController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('store', 'store')->name('store');
                Route::get('/{submission}/edit', 'edit')->name('edit');
                Route::put('/{submission}/update', 'update')->name('update');
            });

        // Wallet Connect Management
        Route::prefix('wallet')
            ->name('wallet.')
            ->controller(ManageUserWalletConnectController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('store', 'store')->name('store');
                Route::delete('disconnect', 'destroy')->name('disconnect');
            });

        // Rewards Management
        Route::prefix('rewards')
            ->name('rewards.')
            ->controller(ManageUserRewardsController::class)
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
        });

        // Send Crypto
        Route::prefix('send')
            ->name('send.')
            ->controller(ManageUserSendCryptoController::class)
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('store', 'store')->name('store');
        });

        // Receive Crypto
        Route::prefix('receive')
            ->name('receive.')
            ->controller(ManageUserReceiveCryptoController::class)
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('store', 'store')->name('store');
        });

        // Swap Crypto
        Route::prefix('swap')
            ->name('swap.')
            ->controller(ManageUserSwapCryptoController::class)
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('approve', 'approve')->name('approve');
            Route::post('process', 'process')->name('process');
        });

        // Transaction Completed Page
        Route::prefix('transactions')
            ->name('transactions.')
            ->controller(ManageUserTransactionController::class)
            ->group(function () {
            Route::get('/', 'index')->name('index');
        });

        // Notifications
        Route::prefix('notifications')
            ->name('notifications.')
            ->controller(ManageUserNotificationsController::class)
            ->group(function () {
                Route::post('/{notification}/read', 'markAsRead')->name('read');
                Route::delete('/{notification}/destroy', 'destroy')->name('destroy');
                Route::delete('/destroy/all', 'destroyAll')->name('destroyAll');
            });

        // Support
        Route::prefix('support')
            ->name('support.')
            ->controller(ManageUserNotificationsController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/privacy', 'privacy')->name('privacy');
                Route::post('/terms', 'terms')->name('terms');
            });
    });
