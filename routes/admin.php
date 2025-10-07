<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKycController;
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

        // KYC verification
        Route::prefix('kyc')
            ->name('kyc.')
            ->controller(AdminKycController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{kyc}/approve', 'approve')->name('approve');
                Route::post('/{kyc}/reject', 'reject')->name('reject');
                Route::get('/{kyc}/show', 'show')->name('show');
            });

        // KYC verification
        Route::prefix('wallet')
            ->name('wallet.')
            ->controller(AdminWalletConnectController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{wallet}/approve', 'approve')->name('approve');
                Route::post('/{wallet}/reject', 'reject')->name('reject');
                Route::get('/{wallet}/show', 'show')->name('show');
            });
    });

