<?php

namespace App\Services;

use App\Http\Resources\TokenResource;
use App\Models\ReceivedCrypto;
use App\Models\User;

class ReceiveCryptoPageService
{
    public function __construct(
        protected MarketDataService $marketDataService,
        protected GatewayHandlerService $gatewayHandler
    ) {
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

        $pendingTransactions = ReceivedCrypto::where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get();

        return [
            'tokens' => $tokens->resolve(),
            'userBalances' => (object) $userBalances,
            'prices' => (object) $this->marketDataService->getPrices(),
            'portfolioChange24h' => $this->calculatePortfolioChange($userBalances, $marketData),
            'popularTokens' => $this->marketDataService->getPopularTokens(6),
            'pendingTransactions' => $pendingTransactions,
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
