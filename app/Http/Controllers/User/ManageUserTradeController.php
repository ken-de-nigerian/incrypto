<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\FundAccountRequest;
use App\Http\Requests\WithdrawAccountRequest;
use App\Services\TradeCryptoPageService;
use App\Services\WalletService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ManageUserTradeController extends Controller
{
    public TradeCryptoPageService $tradeCrypto;
    public WalletService  $walletService;

    public function __construct(TradeCryptoPageService $tradeCryptoPageService, WalletService $walletService)
    {
        $this->tradeCrypto = $tradeCryptoPageService;
        $this->walletService = $walletService;
    }

    /**
     * Display the receive crypto page.
     */
    public function index(): Response
    {
        // Delegate all data gathering to the service class
        $pageData = $this->tradeCrypto->getData(Auth::user());

        return Inertia::render('User/Trade', $pageData);
    }

    /**
     * Handle funding the live account (Crypto to USD conversion).
     * @throws Throwable
     */
    public function fundAccount(FundAccountRequest $request)
    {
        try {
            $this->tradeCrypto->executeAccountFund(
                Auth::user(),
                $request->validated()
            );

            return $this->notify('success', 'Your account has been successfully funded.')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Handle withdrawal (USD to Crypto conversion).
     * @throws Throwable
     */
    public function withdrawAccount(WithdrawAccountRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->tradeCrypto->executeAccountFundWithdrawal(
                Auth::user(),
                $validated
            );

            return $this->notify('success', "Converted \$" . $validated['usd_amount'] . " USD to " . $validated['estimated_crypto'] . " " . $validated['target_symbol'])->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
