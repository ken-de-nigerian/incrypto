<?php

use App\Http\Controllers\User\ChartDataController;
use App\Http\Controllers\User\ManageUserKycController;
use App\Http\Controllers\User\ManageUserProfileController;
use App\Http\Controllers\User\ManageUserReceiveCryptoController;
use App\Http\Controllers\User\ManageUserRewardsController;
use App\Http\Controllers\User\ManageUserSendCryptoController;
use App\Http\Controllers\User\ManageUserSupportController;
use App\Http\Controllers\User\ManageUserSwapCryptoController;
use App\Http\Controllers\User\ManageUserTradeController;
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
    ->middleware(['auth', 'auth.session', 'no.seedphrase', 'can:access-user-dashboard', 'is.admin.impersonating',])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', UserDashboardController::class)->name('dashboard');
        Route::get('/chart/data', ChartDataController::class)->name('chart.data');

        // Profile Management
        Route::prefix('profile')
            ->name('profile.')
            ->controller(ManageUserProfileController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/update/profile', 'updateProfile')->name('update.profile');
                Route::put('/reset/password', 'resetPassword')->name('reset.password');
                Route::patch('/update/wallet/status', 'updateWalletStatus')->name('update.wallet.status');
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

        // Trade Management
        Route::prefix('trade')
            ->name('trade.')
            ->controller(ManageUserTradeController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/forex', 'forex')->name('forex');
                Route::get('/stock', 'stock')->name('stock');
                Route::get('/crypto', 'crypto')->name('crypto');

                Route::get('/chart/{symbol}', 'getChartData')
                    ->where('symbol', '.*')
                    ->name('chart.data');

                Route::post('/execute', 'executeTrade')->name('execute');
                Route::patch('/{trade}/close', 'closeTrade')->name('close');

                Route::get('/investment', 'investment')->name('investment');
                Route::post('/investment/execute', 'executeInvestment')->name('investment.execute');

                Route::get('/network', 'network')->name('network');
                Route::post('/network/execute', 'executeNetwork')->name('network.execute');

                Route::get('/history', 'history')->name('history');
                Route::post('/history/execute', 'executeHistory')->name('history.execute');

                Route::post('/fund', 'fundAccount')->name('fund.account');
                Route::post('/withdraw', 'withdrawAccount')->name('withdraw.account');
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

        // Support
        Route::prefix('support')
            ->name('support.')
            ->controller(ManageUserSupportController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/privacy', 'privacy')->name('privacy');
                Route::get('/aml', 'aml')->name('aml');
                Route::get('/risk', 'risk')->name('risk');
                Route::get('/terms', 'terms')->name('terms');
            });
    });
