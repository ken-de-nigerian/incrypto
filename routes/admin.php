<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminPaymentMethodController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminReceivedCryptoController;
use App\Http\Controllers\Admin\AdminSentCryptoController;
use App\Http\Controllers\Admin\AdminSwapCryptoController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWalletConnectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'can:access-admin-dashboard'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // Profile Management
        Route::prefix('profile')
            ->name('profile.')
            ->controller(AdminProfileController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/update/profile', 'updateProfile')->name('update.profile');
                Route::put('/reset/password', 'resetPassword')->name('reset.password');
            });

        // User management
        Route::prefix('users')
            ->name('users.')
            ->controller(AdminUserController::class)
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/store', 'store')->name('store');

            Route::get('/{user}/show', 'show')->name('show');

            Route::get('/{user}/edit', 'edit')->name('edit');
            Route::post('/{user}/update', 'update')->name('update');

            Route::post('/{user}/funds', 'manageBalance')->name('adjust.balance');
            Route::post('/{user}/email', 'sendEmail')->name('send.email');
            Route::put('/{user}/reset/password', 'resetPassword')->name('reset.password');
            Route::put('/{user}/block', 'suspend')->name('suspend');
            Route::put('/{user}/unblock', 'unsuspend')->name('unsuspend');
            Route::delete('/{user}/delete', 'destroy')->name('destroy');
            Route::get('/{user}/login', 'loginAsUser')->name('login');
            Route::patch('/{user}/update/wallet/status', 'updateWalletStatus')->name('update.wallet.status');
        });

        // KYC verification
        Route::prefix('kyc')
            ->name('kyc.')
            ->controller(AdminKycController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{kyc}/approve', 'approve')->name('approve');
                Route::post('/{kyc}/reject', 'reject')->name('reject');
            });

        // Wallet Management
        Route::prefix('wallet')
            ->name('wallet.')
            ->controller(AdminWalletConnectController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
            });

        // Wallet Management
        Route::prefix('method')
            ->name('method.')
            ->controller(AdminPaymentMethodController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/store', 'store')->name('store');
                Route::patch('/{method}/update', 'update')->name('update');
            });

        // Swapped Cryptos
        Route::prefix('swap')
            ->name('swap.')
            ->controller(AdminSwapCryptoController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{swap}/approve', 'approve')->name('approve');
                Route::post('/{swap}/reject', 'reject')->name('reject');
            });

        // Received Cryptos
        Route::prefix('receive')
            ->name('receive.')
            ->controller(AdminReceivedCryptoController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{received}/approve', 'approve')->name('approve');
                Route::post('/{received}/reject', 'reject')->name('reject');
            });

        // Sent Cryptos
        Route::prefix('send')
            ->name('send.')
            ->controller(AdminSentCryptoController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{sent}/approve', 'approve')->name('approve');
                Route::post('/{sent}/reject', 'reject')->name('reject');
            });

        // Newsletter Notifications
        Route::prefix('notifications')
            ->name('notifications.')
            ->controller(AdminNotificationController::class)
            ->group(function () {
                Route::post('/broadcast', 'broadcast')->name('broadcast');
            });
    });

