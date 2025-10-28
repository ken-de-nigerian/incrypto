<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\FundAccountRequest;
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

            return back()->with('success', 'Your account has been successfully funded.');

        } catch (Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }

//    /**
//     * Handle withdrawal (USD to Crypto conversion).
//     * @param \Illuminate\Http\Request $request
//     * @return \Illuminate\Http\RedirectResponse
//     */
//    public function withdrawAccount(Request $request)
//    {
//        dd($request->all());
//        $user = Auth::user();
//
//        $request->validate([
//            'target_symbol' => ['required', 'string', 'max:10'],
//            'usd_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $user->profile->live_trading_balance], // Check against current balance
//        ]);
//
//        // --- Core Business Logic (Simplified Placeholder) ---
//        try {
//            // 1. Get current price for conversion
//            $conversionRate = app(TradeCryptoPageService::class)->marketDataService->getPrice($request->target_symbol);
//            if ($conversionRate <= 0) {
//                return back()->withErrors(['target_symbol' => 'Invalid or zero-priced token for withdrawal.']);
//            }
//            $cryptoAmount = $request->usd_amount / $conversionRate;
//
//            // 2. Perform balance check and updates
//            // Deduct $request->usd_amount from user's live_trading_balance (checked in validation)
//
//            // 3. Update balances
//            $user->profile->decrement('live_trading_balance', $request->usd_amount);
//
//            // Add $cryptoAmount to user's crypto balance (target_symbol)
//            // NOTE: You would need a method in your WalletService to handle balance incrementing
//            // Example: app(WalletService::class)->creditBalance($user, $request->target_symbol, $cryptoAmount);
//
//
//            return back()->with('success', "Converted \${$request->usd_amount} USD to {$cryptoAmount} {$request->target_symbol}.");
//
//        } catch (Exception $e) {
//            // Log the error
//            return back()->withErrors(['error' => 'Withdrawal failed: ' . $e->getMessage()]);
//        }
//    }
}
