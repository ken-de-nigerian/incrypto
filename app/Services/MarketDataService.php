<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MarketDataService
{
    protected GatewayHandlerService $gateway;

    public function __construct(GatewayHandlerService $gateway)
    {
        $this->gateway = $gateway;
    }

    public function getMarketDataBySymbol(): array
    {
        return Cache::remember('market_data_by_symbol', now()->addMinutes(5), function () {
            $marketData = $this->gateway->fetchGatewaysCrypto() ?? [];
            $marketDataBySymbol = [];
            foreach ($marketData as $crypto) {
                $symbol = strtoupper($crypto['coin']['symbol']);
                $marketDataBySymbol[$symbol] = $crypto['coin'];
            }
            return $marketDataBySymbol;
        });
    }

    public function getPrices(): array
    {
        $prices = [];
        foreach ($this->getMarketDataBySymbol() as $symbol => $coin) {
            $price = (float)($coin['current_price'] ?? 0);
            $prices[$symbol] = $price;
            $prices[$symbol . '_ERC20'] = $price;
            $prices[$symbol . '_BEP20'] = $price;
            $prices[$symbol . '_TRC20'] = $price;
        }
        return $prices;
    }

    public function getGasPrices(): array
    {
        return $this->gateway->fetchGasPrices() ?? [
            'low' => ['gwei' => 25, 'time' => '~3 min', 'usd' => 3.5],
            'medium' => ['gwei' => 35, 'time' => '~1 min', 'usd' => 4.9],
            'high' => ['gwei' => 50, 'time' => '~15 sec', 'usd' => 7.0],
        ];
    }

    public function getPopularTokens(int $count): array
    {
        return array_keys(array_slice($this->getMarketDataBySymbol(), 0, $count, true));
    }

    public function getBaseSymbol(string $symbolWithSuffix): string
    {
        return preg_replace('/_(TRC20|BEP20|ERC20)$/i', '', $symbolWithSuffix);
    }

    public function isValidToken(string $baseSymbol): bool
    {
        return isset($this->getMarketDataBySymbol()[strtoupper($baseSymbol)]);
    }
}
