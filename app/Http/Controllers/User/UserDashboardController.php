<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\GatewayHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use JsonException;

class UserDashboardController extends Controller
{
    public function __invoke(Request $request) {
        return Inertia::render('User/Dashboard', [
            'wallet_balances' => Inertia::defer(fn () => $this->walletBalances()),
        ]);
    }

    private function walletBalances()
    {
        // Fetch auth user
        $user = Auth::user();

        try {
            $wallets = json_decode($user->wallet_balance, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $wallets = [];
            Log::error("Failed to decode wallet balance: " . $e->getMessage());
        }

        // Process all wallets with their USD values and price changes
        $cryptoPrices = $this->getCryptoPrices();
        $processedWallets = [];
        $totalUsdValue = 0;

        foreach ($wallets as $symbol => $crypto) {
            $lowerSymbol = strtolower($crypto['symbol']);
            $currentPrice = $cryptoPrices[$lowerSymbol]['current_price'] ?? 0;
            $balance = $crypto['balance'] ?? 0;
            $usdValue = $balance * $currentPrice;
            $priceChange24h = $cryptoPrices[$lowerSymbol]['price_change_24h'] ?? 0;
            $priceChangePercentage = $cryptoPrices[$lowerSymbol]['price_change_percentage_24h'] ?? 0;
            $profitLoss = $balance * $priceChange24h;

            // Use an indexed array instead of an associative array for the wallets
            $processedWallets[] = [
                'name' => $crypto['name'] ?? $symbol,
                'image' => $crypto['image'] ?? asset('assets/images/default-crypto.png'),
                'balance' => number_format($balance, 4),
                'symbol' => $symbol,
                'usd_value' => number_format($usdValue, 2),
                'profit_loss' => $profitLoss,
                'price_change_percentage' => $priceChangePercentage,
                'is_profit' => $profitLoss >= 0,
            ];

            $totalUsdValue += $usdValue;
        }

        return [
            'wallets' => $processedWallets,
            'totalUsdValue' => number_format($totalUsdValue, 2),
        ];
    }

    /**
     * @return array
     */
    private function getCryptoPrices(): array
    {
        $cryptocurrencies = (new GatewayHandlerService())->fetchGatewaysCrypto();
        $prices = [];

        foreach ($cryptocurrencies as $crypto) {
            $symbol = strtolower($crypto['coin']['symbol']);
            $prices[$symbol] = [
                'current_price' => $crypto['coin']['current_price'],
                'price_change_24h' => $crypto['coin']['price_change_24h'] ?? 0,
                'price_change_percentage_24h' => $crypto['coin']['price_change_percentage_24h'] ?? 0,
            ];
        }

        return $prices;
    }
}
