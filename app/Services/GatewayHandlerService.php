<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GatewayHandlerService
{
    /**
     * Cache lifetimes in seconds
     */
    private const CACHE_TTL = [
        'PRICE_DATA' => 300,       // 5 minutes
        'GAS_PRICE_DATA' => 120,   // 2 minutes
        'WALLET_DATA' => 43200,    // 12 hours
        'CHART_DATA' => 300,       // 5 minutes
    ];

    /**
     * Retry logic constants
     */
    private const MAX_RETRIES = 3;
    private const RETRY_BASE_DELAY = 1;
    private const RATE_LIMIT_MAX_RETRIES = 2; // Retry rate limits before switching
    private const RATE_LIMIT_RETRY_DELAY = 5; // Seconds to wait on the rate limit

    private const CHAIN_IDS = [
        'Ethereum' => 1,
        'BSC' => 56,
        'Polygon' => 137,
        'Arbitrum' => 42161,
        'Optimism' => 10,
    ];

    private const API_PROVIDERS = [
        'price' => ['coingecko', 'coinpaprika', 'cryptocompare'],
        'chart' => ['coingecko', 'coinpaprika'],
    ];

    private const SYMBOL_MAPPINGS = [
        'usdt_trc20' => [
            'coingecko' => 'tether',
            'coinpaprika' => 'usdt-tether',
            'cryptocompare' => 'USDT'
        ],
        'usdt_bep20' => [
            'coingecko' => 'tether',
            'coinpaprika' => 'usdt-tether',
            'cryptocompare' => 'USDT'
        ],
        'binancecoin' => [
            'coingecko' => 'binancecoin',
            'coinpaprika' => 'bnb-binance-coin',
            'cryptocompare' => 'BNB'
        ],
    ];

    public function getGateways(): array
    {
        try {
            $wallets = config('gateways.wallet_addresses');

            if (!is_array($wallets)) {
                return [];
            }

            return collect($wallets)
                ->where('status', '1')
                ->sortBy('status')
                ->values()
                ->toArray();
        } catch (Throwable $e) {
            Log::error('Error in getGateways(): ' . $e->getMessage());
            return [];
        }
    }

    public function fetchChartData(string $symbol, float $days = 1): array
    {
        if ($days < 0.01 || $days > 365) {
            Log::warning('Invalid days parameter for chart data', ['days' => $days]);
            return ['error' => 'Days must be between 0.01 and 365'];
        }

        $cacheKey = "chart_{$symbol}_$days";

        $cached = Cache::get($cacheKey);
        if ($cached && ($cached['success'] ?? false)) {
            return $cached;
        }

        $result = $this->fetchChartDataWithProviderFallback($symbol, $days);

        if ($result['success'] ?? false) {
            Cache::put($cacheKey, $result, self::CACHE_TTL['CHART_DATA']);
        }

        return $result;
    }

    private function fetchChartDataWithProviderFallback(string $symbol, float $days): array
    {
        foreach (self::API_PROVIDERS['chart'] as $provider) {
            $result = $this->fetchChartDataWithRetry($provider, $symbol, $days);

            if ($result['success']) {
                return $result;
            }

            Log::warning("Failed to fetch chart data from $provider after retries, trying next provider", [
                'symbol' => $symbol,
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }

        Log::error('All chart data providers failed', ['symbol' => $symbol]);
        return [
            'success' => false,
            'error' => 'All API providers failed',
            'message' => 'Unable to fetch chart data from any provider'
        ];
    }

    private function fetchChartDataWithRetry(string $provider, string $symbol, float $days): array
    {
        $maxRetries = self::MAX_RETRIES;
        $rateLimitRetries = 0;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $result = $this->fetchChartDataFromProvider($provider, $symbol, $days);

            if ($result['success']) {
                return $result;
            }

            if (isset($result['status']) && $result['status'] === 429) {
                $rateLimitRetries++;

                if ($rateLimitRetries > self::RATE_LIMIT_MAX_RETRIES) {
                    Log::warning("Rate limit retry limit exceeded for $provider", [
                        'symbol' => $symbol,
                        'attempts' => $rateLimitRetries
                    ]);
                    return $result;
                }

                sleep(self::RATE_LIMIT_RETRY_DELAY);
                continue;
            }

            if ($attempt < $maxRetries - 1) {
                $delay = self::RETRY_BASE_DELAY * pow(2, $attempt);
                sleep($delay);
            }
        }

        return [
            'success' => false,
            'error' => "Failed after $maxRetries attempts"
        ];
    }

    private function fetchChartDataFromProvider(string $provider, string $symbol, float $days): array
    {
        try {
            return match ($provider) {
                'coingecko' => $this->fetchChartDataFromCoinGecko($symbol, $days),
                'coinpaprika' => $this->fetchChartDataFromCoinPaprika($symbol, $days),
                default => ['success' => false, 'error' => 'Unknown provider'],
            };
        } catch (Throwable $e) {
            Log::error("Exception fetching chart data from $provider", [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @throws Exception
     */
    private function fetchChartDataFromCoinGecko(string $symbol, float $days): array
    {
        try {
            $mappedSymbol = $this->getMappedSymbol($symbol, 'coingecko');
            $apiKey = config('services.coingecko.key', '');
            $url = "https://api.coingecko.com/api/v3/coins/$mappedSymbol/market_chart";

            $params = [
                'vs_currency' => 'usd',
                'days' => $days,
            ];

            if ($apiKey) {
                $params['x_cg_api_key'] = $apiKey;
            }

            $response = Http::timeout(10)->get($url, $params);

            if ($response->status() === 429) {
                return ['success' => false, 'error' => 'Rate limit exceeded', 'status' => 429];
            }

            if ($response->successful()) {
                $data = $response->json();

                if (!isset($data['prices']) || !is_array($data['prices'])) {
                    throw new Exception('Invalid chart data structure received');
                }

                return ['success' => true, 'data' => $data, 'provider' => 'coingecko'];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch from CoinGecko',
                'status' => $response->status()
            ];

        } catch (ConnectionException $e) {
            return ['success' => false, 'error' => 'Connection timeout ' . $e];
        }
    }

    private function fetchChartDataFromCoinPaprika(string $symbol, float $days): array
    {
        try {
            $mappedSymbol = $this->getMappedSymbol($symbol, 'coinpaprika');

            $interval = $days <= 1 ? '1h' : ($days <= 7 ? '6h' : '1d');
            $start = now()->subDays($days)->toIso8601String();

            $url = "https://api.coinpaprika.com/v1/tickers/$mappedSymbol/historical";

            $response = Http::timeout(10)->get($url, [
                'start' => $start,
                'interval' => $interval,
            ]);

            if ($response->status() === 429) {
                return ['success' => false, 'error' => 'Rate limit exceeded', 'status' => 429];
            }

            if ($response->successful()) {
                $data = $response->json();

                $prices = collect($data)->map(function ($item) {
                    return [
                        strtotime($item['timestamp']) * 1000,
                        $item['price']
                    ];
                })->values()->toArray();

                return [
                    'success' => true,
                    'data' => ['prices' => $prices],
                    'provider' => 'coinpaprika'
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch from CoinPaprika',
                'status' => $response->status()
            ];

        } catch (ConnectionException $e) {
            return ['success' => false, 'error' => 'Connection timeout ' . $e];
        }
    }

    private function getMappedSymbol(string $symbol, string $provider): string
    {
        return self::SYMBOL_MAPPINGS[$symbol][$provider] ?? $symbol;
    }

    public function fetchGasPrices(string $chain = 'Ethereum'): array
    {
        $cacheKey = "etherscan_gas_prices_$chain";
        $apiKey = config('services.etherscan.key');

        if (!$apiKey) {
            Log::error('Etherscan API key is not configured.');
            return $this->getFallbackGasPrices();
        }

        $chainId = self::CHAIN_IDS[$chain] ?? 1;

        return Cache::remember($cacheKey, self::CACHE_TTL['GAS_PRICE_DATA'], function () use ($apiKey, $chain, $chainId) {
            try {
                $ethPrice = $this->fetchEthPrice();
                $url = "https://api.etherscan.io/v2/api?module=gastracker&action=gasoracle&chainid=$chainId&apikey=$apiKey";
                $response = $this->fetchData(
                    "etherscan_gas_oracle_$chain",
                    $url,
                    self::CACHE_TTL['GAS_PRICE_DATA'],
                    "Failed to fetch Etherscan V2 Gas Oracle data for chain $chain"
                );

                if (($response['status'] ?? '0') !== '1' || !isset($response['result'])) {
                    throw new Exception('Invalid response from Etherscan V2 Gas Oracle API: ' . ($response['message'] ?? 'Unknown error'));
                }

                $result = $response['result'];
                $safeGasPrice = $result['safeGasPrice'] ?? $result['SafeGasPrice'] ?? null;
                $proposeGasPrice = $result['proposeGasPrice'] ?? $result['ProposeGasPrice'] ?? null;
                $fastGasPrice = $result['fastGasPrice'] ?? $result['FastGasPrice'] ?? null;

                if (!$safeGasPrice || !$proposeGasPrice || !$fastGasPrice) {
                    throw new Exception('Missing gas price fields in Etherscan V2 response');
                }

                $gweiToUsd = fn($gwei) => ($gwei * 21000 * $ethPrice) / 1e9;

                return [
                    'low' => [
                        'gwei' => (float) $safeGasPrice,
                        'time' => '~3 min',
                        'usd' => round($gweiToUsd($safeGasPrice), 2),
                    ],
                    'medium' => [
                        'gwei' => (float) $proposeGasPrice,
                        'time' => '~1 min',
                        'usd' => round($gweiToUsd($proposeGasPrice), 2),
                    ],
                    'high' => [
                        'gwei' => (float) $fastGasPrice,
                        'time' => '~15 sec',
                        'usd' => round($gweiToUsd($fastGasPrice), 2),
                    ],
                ];
            } catch (Throwable $e) {
                Log::error('Failed to fetch gas prices: ' . $e->getMessage(), [
                    'api_key' => substr($apiKey, 0, 4) . '****',
                    'chain' => $chain,
                    'chainid' => $chainId,
                ]);
                return $this->getFallbackGasPrices();
            }
        });
    }

    private function getFallbackGasPrices(): array
    {
        return [
            'low' => ['gwei' => 25, 'time' => '~3 min', 'usd' => 3.50],
            'medium' => ['gwei' => 35, 'time' => '~1 min', 'usd' => 4.90],
            'high' => ['gwei' => 50, 'time' => '~15 sec', 'usd' => 7.00],
        ];
    }

    private function fetchEthPrice(): float
    {
        $cacheKey = 'eth_price';

        $cached = Cache::get($cacheKey);
        if ($cached && is_numeric($cached) && $cached > 0) {
            return (float) $cached;
        }

        $rateLimitRetries = 0;

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $url = 'https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd';
                $apiKey = config('services.coingecko.key', '');

                if ($apiKey) {
                    $url .= '&x_cg_api_key=' . $apiKey;
                }

                $response = Http::timeout(10)->get($url);

                if ($response->successful() && isset($response->json()['ethereum']['usd'])) {
                    $price = (float) $response->json()['ethereum']['usd'];
                    Cache::put($cacheKey, $price, self::CACHE_TTL['PRICE_DATA']);
                    return $price;
                }

                if ($response->status() === 429) {
                    $rateLimitRetries++;

                    if ($rateLimitRetries > self::RATE_LIMIT_MAX_RETRIES) {
                        Log::warning('Rate limit retry limit exceeded for ETH price');
                        break;
                    }

                    sleep(self::RATE_LIMIT_RETRY_DELAY);
                    continue;
                }

                if ($attempt < self::MAX_RETRIES - 1) {
                    sleep(self::RETRY_BASE_DELAY * pow(2, $attempt));
                }

            } catch (Throwable $e) {
                Log::warning('Failed to fetch ETH price from CoinGecko', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt + 1
                ]);

                if ($attempt < self::MAX_RETRIES - 1) {
                    sleep(self::RETRY_BASE_DELAY * pow(2, $attempt));
                }
            }
        }

        try {
            $response = Http::timeout(10)->get('https://api.coinpaprika.com/v1/tickers/eth-ethereum');

            if ($response->successful() && isset($response->json()['quotes']['USD']['price'])) {
                $price = (float) $response->json()['quotes']['USD']['price'];
                Cache::put($cacheKey, $price, self::CACHE_TTL['PRICE_DATA']);
                return $price;
            }
        } catch (Throwable $e) {
            Log::warning('Failed to fetch ETH price from CoinPaprika', ['error' => $e->getMessage()]);
        }

        Log::warning('Using fallback ETH price');
        return 2000.0;
    }

    private function fetchCoinGeckoMarketData(array $coinIds): array
    {
        if (empty($coinIds)) {
            return [];
        }

        $cacheKey = "coinGeckoSpecificCoins_" . implode('_', $coinIds);

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $result = $this->fetchMarketDataFromCoinGeckoWithRetry($coinIds);

        if (!empty($result)) {
            Cache::put($cacheKey, $result, self::CACHE_TTL['PRICE_DATA']);
            return $result;
        }

        Log::warning('Falling back to CoinPaprika for market data after CoinGecko failed');
        $fallbackResult = $this->fetchMarketDataFromCoinPaprika($coinIds);

        if (!empty($fallbackResult)) {
            Cache::put($cacheKey, $fallbackResult, self::CACHE_TTL['PRICE_DATA']);
        }

        return $fallbackResult;
    }

    private function fetchMarketDataFromCoinGeckoWithRetry(array $coinIds): array
    {
        $maxRetries = self::MAX_RETRIES;
        $rateLimitRetries = 0;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $result = $this->fetchMarketDataFromCoinGecko($coinIds);

                if (!empty($result)) {
                    return $result;
                }

                if ($attempt < $maxRetries - 1) {
                    $delay = self::RETRY_BASE_DELAY * pow(2, $attempt);
                    sleep($delay);
                }

            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate limit')) {
                    $rateLimitRetries++;

                    if ($rateLimitRetries > self::RATE_LIMIT_MAX_RETRIES) {
                        Log::warning('Rate limit retry limit exceeded for CoinGecko market data', [
                            'attempts' => $rateLimitRetries
                        ]);
                        break;
                    }

                    sleep(self::RATE_LIMIT_RETRY_DELAY);
                    continue;
                }

                if ($attempt < $maxRetries - 1) {
                    $delay = self::RETRY_BASE_DELAY * pow(2, $attempt);
                    sleep($delay);
                }
            }
        }

        return [];
    }

    private function fetchMarketDataFromCoinGecko(array $coinIds): array
    {
        $cacheKey = "coinGeckoSpecificCoins_" . implode('_', $coinIds);
        $ids = implode(',', $coinIds);
        $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=$ids&order=market_cap_desc&sparkline=false";

        try {
            return $this->fetchData(
                $cacheKey,
                $url,
                self::CACHE_TTL['PRICE_DATA'],
                'Failed to fetch CoinGecko market data for specific IDs'
            );
        } catch (Throwable $e) {
            Log::warning('CoinGecko market data fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchMarketDataFromCoinPaprika(array $coinIds): array
    {
        $results = [];

        foreach ($coinIds as $coinId) {
            try {
                $mappedId = $this->getMappedSymbol($coinId, 'coinpaprika');
                $response = Http::timeout(10)->get("https://api.coinpaprika.com/v1/tickers/$mappedId");

                if ($response->successful()) {
                    $data = $response->json();

                    $results[] = [
                        'id' => $coinId,
                        'symbol' => $data['symbol'] ?? '',
                        'name' => $data['name'] ?? '',
                        'current_price' => $data['quotes']['USD']['price'] ?? 0,
                        'market_cap' => $data['quotes']['USD']['market_cap'] ?? 0,
                        'price_change_percentage_24h' => $data['quotes']['USD']['percent_change_24h'] ?? 0,
                        'total_volume' => $data['quotes']['USD']['volume_24h'] ?? 0,
                    ];
                }
            } catch (Throwable $e) {
                Log::warning("Failed to fetch $coinId from CoinPaprika", ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    public function fetchGatewaysCrypto(): array
    {
        $gateways = $this->getGateways();
        if (empty($gateways)) {
            return [];
        }

        $coinIds = collect($gateways)
            ->pluck('coingecko_id')
            ->filter()
            ->unique()
            ->all();

        if (empty($coinIds)) {
            Log::warning('No CoinGecko IDs found in gateway configuration.');
            return [];
        }

        $coinData = $this->fetchCoinGeckoMarketData($coinIds);
        if (empty($coinData)) {
            Log::warning('Received empty market data from all providers.', ['ids' => $coinIds]);
            return [];
        }

        $coinDataById = collect($coinData)->keyBy('id')->all();

        $result = [];
        foreach ($gateways as $gateway) {
            $coinId = $gateway['coingecko_id'] ?? null;
            if ($coinId && isset($coinDataById[$coinId])) {
                $result[] = array_merge($gateway, ['coin' => $coinDataById[$coinId]]);
            }
        }

        return $result;
    }

    public function getWallets(): array
    {
        $cacheKey = "cryptoCompareWallets";
        $url = "https://min-api.cryptocompare.com/data/wallets/general";

        if ($apiKey = config('services.cryptocompare.key')) {
            $url .= "?api_key=" . $apiKey;
        }

        $data = $this->fetchData(
            $cacheKey,
            $url,
            self::CACHE_TTL['WALLET_DATA'],
            'Failed to fetch wallet data'
        );

        return [
            'Data' => isset($data['Data']) ? $this->sortWalletData($data['Data']) : [],
            'Message' => $data['Message'] ?? ($data['message'] ?? ''),
        ];
    }

    private function sortWalletData(array $wallets): array
    {
        usort($wallets, fn($a, $b) => strcmp($a['Name'] ?? '', $b['Name'] ?? ''));
        return $wallets;
    }

    public function getWallet(string $walletId): ?array
    {
        $wallets = $this->getWallets();
        foreach (($wallets['Data']) as $wallet) {
            if ($wallet['Id'] === $walletId) {
                return $wallet;
            }
        }

        return null;
    }

    private function fetchData(string $cacheKey, string $apiUrl, int $ttl, string $errorContext = ''): array
    {
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $response = $this->fetchFromAPIWithRetry($apiUrl);

        if ($response['error'] ?? false) {
            Log::warning("API fetch failed after all retries.", [
                'context' => $errorContext,
                'url' => $apiUrl,
                'final_status' => $response['status'] ?? 'N/A'
            ]);
            return [];
        }

        $data = $response['data'] ?? [];

        if (!empty($data)) {
            Cache::put($cacheKey, $data, $ttl);
        }

        return $data;
    }

    private function fetchFromAPIWithRetry(string $apiUrl): array
    {
        $retries = 0;
        $response = [];

        while ($retries < self::MAX_RETRIES) {
            $response = $this->makeApiRequest($apiUrl);

            if ($response['status'] >= 200 && $response['status'] < 300) {
                return ['error' => false, 'data' => $response['data']];
            }

            if ($response['status'] === 429) {
                Log::warning("Rate limit hit, switching to fallback provider", ['url' => $apiUrl]);
                return ['error' => true, 'status' => 429, 'data' => []];
            }

            $retries++;
            if ($retries < self::MAX_RETRIES) {
                $delay = self::RETRY_BASE_DELAY * pow(2, $retries - 1);
                sleep($delay);
            }
        }

        return ['error' => true, 'status' => $response['status'] ?? 500, 'data' => []];
    }

    private function makeApiRequest(string $apiUrl): array
    {
        try {
            $apiKey = config('services.coingecko.key', '');
            $urlWithKey = $apiKey && !str_contains($apiUrl, 'etherscan') && !str_contains($apiUrl, 'coinpaprika')
                ? $apiUrl . (str_contains($apiUrl, '?') ? '&' : '?') . 'x_cg_api_key=' . $apiKey
                : $apiUrl;

            $response = Http::timeout(20)->get($urlWithKey);

            return [
                'status' => $response->status(),
                'data' => $response->json() ?? [],
            ];
        } catch (ConnectionException $e) {
            Log::error("API request connection exception", ['url' => $apiUrl, 'error' => $e->getMessage()]);
            return ['status' => 504, 'data' => []];
        } catch (Exception $e) {
            Log::error("Generic API request exception", ['url' => $apiUrl, 'error' => $e->getMessage()]);
            return ['status' => 500, 'data' => []];
        }
    }
}
