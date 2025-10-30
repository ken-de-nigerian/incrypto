<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\FundAccountRequest;
use App\Http\Requests\WithdrawAccountRequest;
use App\Services\GatewayHandlerService;
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
    public WalletService $walletService;

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
     * Display the Forex trading page with all required data
     */
    public function forex(): Response
    {
        $user = Auth::user();

        // Get base page data from service
        $pageData = $this->tradeCrypto->getData($user);

        // Add Forex-specific data
        $pageData['forexPairs'] = $this->getForexPairs();
        $pageData['trades'] = $this->getUserTrades($user);
        $pageData['tradingStats'] = $this->getTradingStats($user);

        return Inertia::render('User/Forex', $pageData);
    }

    /**
     * Get forex pairs data from database or API
     * Adjust based on your actual data source
     */
    private function getForexPairs(): array
    {
        return (new GatewayHandlerService())->fetchForexPairs();
    }

    /**
     * Get user's trading history
     */
    private function getUserTrades($user): array
    {
        // Replace with actual database query
        // Example: return $user->trades()->latest()->take(10)->get();
        return [
            [
                'id' => 1,
                'pair' => 'EUR/USD',
                'pairName' => '2 hours ago',
                'type' => 'Buy',
                'status' => 'Open',
                'pnl' => 145.50,
                'timestamp' => '2 hours ago'
            ],
            [
                'id' => 2,
                'pair' => 'GBP/USD',
                'pairName' => '4 hours ago',
                'type' => 'Sell',
                'status' => 'Closed',
                'pnl' => -52.25,
                'timestamp' => '4 hours ago'
            ],
            [
                'id' => 3,
                'pair' => 'USD/JPY',
                'pairName' => '1 day ago',
                'type' => 'Buy',
                'status' => 'Closed',
                'pnl' => 327.80,
                'timestamp' => '1 day ago'
            ],
            [
                'id' => 4,
                'pair' => 'AUD/USD',
                'pairName' => '1 day ago',
                'type' => 'Sell',
                'status' => 'Closed',
                'pnl' => 89.15,
                'timestamp' => '1 day ago'
            ]
        ];
    }

    /**
     * Get trading statistics for the user
     */
    private function getTradingStats($user): array
    {
        // Calculate actual stats from user's trades
        // This is example data - replace with real calculations
        return [
            [
                'label' => 'Open Trades',
                'value' => '2',
                'color' => 'text-primary'
            ],
            [
                'label' => 'Win Rate',
                'value' => '65%',
                'color' => 'text-emerald-500'
            ],
            [
                'label' => 'Leverage',
                'value' => '1:100',
                'color' => 'text-warning'
            ],
            [
                'label' => 'Risk/Reward',
                'value' => '1:2.5',
                'color' => 'text-primary'
            ]
        ];
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
