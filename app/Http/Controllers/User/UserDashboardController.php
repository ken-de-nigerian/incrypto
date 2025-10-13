<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\TokenResource;
use App\Models\User;
use App\Services\GatewayHandlerService;
use App\Services\MarketDataService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserDashboardController extends Controller
{
    protected GatewayHandlerService $gatewayHandler;
    protected MarketDataService $marketDataService;

    public function __construct(GatewayHandlerService $gatewayHandler, MarketDataService $marketDataService)
    {
        $this->gatewayHandler = $gatewayHandler;
        $this->marketDataService = $marketDataService;
    }

    public function __invoke(Request $request) {
        $cryptoListData = $this->getData(Auth::user());
        return Inertia::render('User/Dashboard', [
            'wallet_balances' => Inertia::defer(fn () => $this->walletBalances()),
            'referred_users' => Inertia::defer(fn () => $this->referredUsers()),
            ...$cryptoListData
        ]);
    }

    public function referredUsers()
    {
        $user = Auth::user();
        return $user->referrals()
            ->select(['id', 'first_name', 'last_name', 'created_at'])
            ->latest()
            ->limit(3)
            ->get();
    }

    private function walletBalances()
    {
        // Fetch auth user
        $user = Auth::user();
        $walletService = new WalletService($user, $this->gatewayHandler);
        $fullWalletData = $walletService->getFullWalletData();

        $cryptoPrices = $this->getCryptoPrices();
        $processedWallets = [];
        $totalUsdValue = 0;

        foreach ($fullWalletData as $symbol => $crypto) {
            $lowerSymbol = strtolower($crypto['symbol']);
            $currentPrice = $cryptoPrices[$lowerSymbol]['current_price'] ?? 0;
            $balance = $crypto['balance'] ?? 0;
            $usdValue = $balance * $currentPrice;
            $priceChange24h = $cryptoPrices[$lowerSymbol]['price_change_24h'] ?? 0;
            $priceChangePercentage = $cryptoPrices[$lowerSymbol]['price_change_percentage_24h'] ?? 0;
            $profitLoss = $balance * $priceChange24h;

            $processedWallets[] = [
                'name' => $crypto['name'] ?? $symbol,
                'image' => $crypto['image'] ?? asset('assets/images/crypto.png'),
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
        $cryptocurrencies = $this->gatewayHandler->fetchGatewaysCrypto();
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

    public function getData(User $user): array
    {
        $walletService = new WalletService($user, $this->gatewayHandler);
        $marketData = $this->marketDataService->getMarketDataBySymbol();
        $userBalances = $walletService->getBalances();
        $fullWalletData = $walletService->getFullWalletData();
        $gatewaysByCode = collect($this->gatewayHandler->getGateways())->keyBy('method_code');

        // Use a Resource Collection to format the token array cleanly
        $tokens = TokenResource::collection(
            collect($userBalances)->map(function ($balance, $symbol) use ($marketData, $fullWalletData, $gatewaysByCode) {
                return [
                    'symbol' => $symbol,
                    'balance' => $balance,
                    'market_data' => $marketData[$this->marketDataService->getBaseSymbol($symbol)] ?? [],
                    'wallet_data' => $fullWalletData[$symbol] ?? [],
                    'gateway' => isset($fullWalletData[$symbol]['id']) ? $gatewaysByCode->get($fullWalletData[$symbol]['id']) : null,
                ];
            })->values()
        );

        return [
            'tokens' => $tokens->resolve(),
            'userBalances' => (object) $userBalances,
            'prices' => (object) $this->marketDataService->getPrices(),
            'portfolioChange24h' => $this->calculatePortfolioChange($userBalances, $marketData),
        ];
    }

    private function calculatePortfolioChange(array $userBalances, array $marketDataBySymbol): float
    {
        $currentValue = 0;
        $previousValue = 0;

        foreach ($userBalances as $symbol => $balance) {
            $baseSymbol = $this->marketDataService->getBaseSymbol($symbol);
            $coin = $marketDataBySymbol[$baseSymbol] ?? [];
            $currentPrice = $coin['current_price'] ?? 0;
            $priceChange = $coin['price_change_percentage_24h'] ?? 0;

            if ($currentPrice > 0) {
                $currentValue += $balance * $currentPrice;
                $previousPrice = $currentPrice / (1 + ($priceChange / 100));
                $previousValue += $balance * $previousPrice;
            }
        }

        return $previousValue > 0 ? (($currentValue - $previousValue) / $previousValue) * 100 : 0;
    }
}
