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
        'PRICE_DATA' => 300, // 5 minutes
        'GAS_PRICE_DATA' => 120, // 2 minutes
        'WALLET_DATA' => 43200, // 12 hours
        'CHART_DATA' => 300, // 5 minutes
        'CRYPTOS_LIST' => 3600, // 1 hour
    ];

    /**
     * Retry logic constants
     */
    private const MAX_RETRIES = 3;
    private const RETRY_BASE_DELAY = 1;
    private const RATE_LIMIT_MAX_RETRIES = 2;
    private const RATE_LIMIT_RETRY_DELAY = 5;
    private const CHAIN_IDS = [
        'Ethereum' => 1,
        'BSC' => 56,
        'Polygon' => 137,
        'Arbitrum' => 42161,
        'Optimism' => 10,
    ];

    private const API_PROVIDERS = [
        'price' => ['cryptocompare', 'coinmarketcap', 'coingecko'],
        'chart' => ['coingecko', 'coinmarketcap', 'coinpaprika'],
    ];

    private const SYMBOL_MAPPINGS = [
        'usdt_trc20' => [
            'coingecko' => 'tether',
            'coinpaprika' => 'usdt-tether',
            'cryptocompare' => 'USDT',
            'coinmarketcap' => 'USDT',
        ],
        'usdt_bep20' => [
            'coingecko' => 'tether',
            'coinpaprika' => 'usdt-tether',
            'cryptocompare' => 'USDT',
            'coinmarketcap' => 'USDT',
        ],
        'binancecoin' => [
            'coingecko' => 'binancecoin',
            'coinpaprika' => 'bnb-binance-coin',
            'cryptocompare' => 'BNB',
            'coinmarketcap' => 'BNB',
        ],
        'ethereum' => [
            'coingecko' => 'ethereum',
            'coinpaprika' => 'eth-ethereum',
            'cryptocompare' => 'ETH',
            'coinmarketcap' => 'ETH',
        ],
    ];

    /**
     * Fetch historical chart data for a forex pair
     */
    public function fetchForexChartData(string $symbol): array
    {
        $allPairs = $this->getAllPairs();
        $pairData = collect($allPairs)->firstWhere('symbol', $symbol);

        if (!$pairData) {
            return ['success' => false, 'error' => 'Pair not found'];
        }

        $apiKey = config('services.polygon.key');
        if (!$apiKey) {
            return ['success' => false, 'error' => 'API key not configured'];
        }

        $cacheKey = "forex_chart_$symbol";

        $cached = Cache::get($cacheKey);
        if ($cached && ($cached['success'] ?? false)) {
            return $cached;
        }

        try {

            $to = now()->format('Y-m-d');
            $two_years_ago = now()->subYears(2);
            $from = $two_years_ago->subDays()->format('Y-m-d');
            $encodedApiKey = urlencode($apiKey);

            // Construct the base URL
            $url = "https://api.massive.com/v2/aggs/ticker/{$pairData['polygon']}/range/1/day/$from/$to?adjusted=true&sort=asc&limit=240&apiKey=$encodedApiKey";
            $response = Http::timeout(30)->get($url);

            if ($response->status() === 429) {
                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'status' => 429
                ];
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch chart data',
                    'status' => $response->status()
                ];
            }

            $data = $response->json();

            if (empty($data['results'])) {
                return ['success' => false, 'error' => 'No data available'];
            }

            $resultsCollection = collect($data['results']);

            // Transform to format expected by chart
            $chartData = $resultsCollection->map(function ($candle) {
                return [
                    'time' => $candle['t'] / 1000,
                    'open' => $candle['o'],
                    'high' => $candle['h'],
                    'low' => $candle['l'],
                    'close' => $candle['c'],
                    'volume' => $candle['v'] ?? 0
                ];
            })->toArray();

            // Get the last (latest) candle's raw data
            $latestResult = $resultsCollection->last();

            // Extract and format the specific fields for the frontend panels
            $latestHigh = (string) ($latestResult['h'] ?? '0');
            $latestLow = (string) ($latestResult['l'] ?? '0');
            $latestVolume = (string) ($latestResult['v'] ?? '0');

            $result = [
                'success' => true,
                'data' => $chartData,
                'symbol' => $symbol,
                'timeframe' => '1D',
                'high' => $latestHigh,
                'low' => $latestLow,
                'volume' => $latestVolume,
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL['CHART_DATA']);

            return $result;
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all available forex pairs with flag image URLs included.
     */
    public function getAllPairs(): array
    {
        // 1. Get the currency-to-country-code map
        $currencyMap = CurrencyToCountryCodeMapService::getCurrencyToCountryCodeMap();

        // 2. Your original list of pairs
        $pairsData = pairsDataService::getPairsData();

        // 3. Process the array
        return array_map(function($pair) use ($currencyMap) {
            $parts = explode('/', $pair['symbol']);
            $baseCurrency = $parts[0] ?? null;
            $quoteCurrency = $parts[1] ?? null;

            // Get country codes, defaulting to 'xx' (unknown flag)
            $baseFlagCode = strtolower($currencyMap[$baseCurrency] ?? 'xx');
            $quoteFlagCode = strtolower($currencyMap[$quoteCurrency] ?? 'xx');

            // Build the CDN URLs
            $baseFlagUrl = "https://flagcdn.com/$baseFlagCode.svg";
            $quoteFlagUrl = "https://flagcdn.com/$quoteFlagCode.svg";

            // Add the new data to the existing pair array
            return array_merge($pair, [
                'baseCurrency' => $baseCurrency,
                'quoteCurrency' => $quoteCurrency,
                'baseFlagUrl' => $baseFlagUrl,
                'quoteFlagUrl' => $quoteFlagUrl,
            ]);
        }, $pairsData);
    }

    /**
     * Gets market prices via CoinGecko API without pagination and caches results.
     */
    public function getCryptos(): array
    {
        $cacheKey = "coinGeckoCryptosList";
        $ttl = self::CACHE_TTL['CRYPTOS_LIST'];
        return Cache::remember($cacheKey, $ttl, function () {
            $data = $this->coinGeckoNoPagination();
            if (empty($data)) {
                Log::warning('Failed to fetch full cryptos list from CoinGecko.');
                return [];
            }
            usort($data, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            return $this->transformCoinGeckoCryptos($data);
        });
    }

    /**
     * Gets raw market data from CoinGecko API.
     */
    private function coinGeckoNoPagination(): array
    {
        $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&sparkline=false";
        return $this->fetchData(
            "raw_coingecko_no_pagination_temp",
            $url,
            self::CACHE_TTL['CRYPTOS_LIST'],
            "Failed to fetch CoinGecko top cryptos"
        );
    }

    /**
     * Transforms raw CoinGecko market data to cache only required fields.
     */
    private function transformCoinGeckoCryptos(array $rawData): array
    {
        return collect($rawData)->map(function ($coin) {
            return [
                'id' => $coin['id'] ?? null,
                'symbol' => $coin['symbol'] ?? '',
                'name' => $coin['name'] ?? '',
                'image' => $coin['image'] ?? asset('assets/images/crypto.png'),
            ];
        })
            ->filter(fn($coin) => $coin['id'] !== null)
            ->values()
            ->toArray();
    }

    public function getGateways(): array
    {
        try {
            $wallets = config('gateways.wallet_addresses');
            if (!is_array($wallets)) {
                return [];
            }
            $gateways = collect($wallets)
                ->where('status', '1')
                ->sortBy('status')
                ->values()
                ->toArray();
            if (empty($gateways)) {
                return [];
            }
            $coinGeckoIds = collect($gateways)
                ->pluck('coingecko_id')
                ->filter()
                ->unique()
                ->all();
            if (empty($coinGeckoIds)) {
                return $gateways;
            }
            $cryptos = $this->getCryptos();
            $cryptoMap = collect($cryptos)
                ->keyBy('id')
                ->all();
            return array_map(function ($gateway) use ($cryptoMap) {
                $coinId = $gateway['coingecko_id'] ?? null;
                if ($coinId && isset($cryptoMap[$coinId])) {
                    $gateway['image'] = $cryptoMap[$coinId]['image'];
                }
                return $gateway;
            }, $gateways);
        } catch (Throwable $e) {
            Log::error('Error in getGateways(): ' . $e->getMessage());
            return [];
        }
    }

    private function getMappedSymbol(string $symbol, string $provider): string
    {
        return self::SYMBOL_MAPPINGS[$symbol][$provider] ?? $symbol;
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
                'coinmarketcap' => $this->fetchChartDataFromCoinMarketCap($symbol, $days),
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

    private function fetchChartDataFromCoinMarketCap(string $symbol, float $days): array
    {
        $mappedSymbol = strtoupper($this->getMappedSymbol($symbol, 'coinmarketcap'));
        $apiKey = config('services.coinmarketcap.key');
        if (!$apiKey) {
            Log::warning('CoinMarketCap API key not configured, skipping chart provider.');
            return ['success' => false, 'error' => 'API key missing', 'status' => 401];
        }
        try {
            $timeStart = now()->subDays($days)->toIso8601String();
            $timeEnd = now()->toIso8601String();
            $interval = match (true) {
                $days <= 1 => '5m',
                $days <= 7 => '30m',
                $days <= 30 => '4h',
                default => '1d',
            };
            $url = "https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/historical";
            $response = Http::timeout(10)
                ->withHeaders(['X-CMC_PRO_API_KEY' => $apiKey])
                ->get($url, [
                    'symbol' => $mappedSymbol,
                    'convert' => 'USD',
                    'time_start' => $timeStart,
                    'time_end' => $timeEnd,
                    'interval' => $interval,
                ]);
            if ($response->status() === 429) {
                return ['success' => false, 'error' => 'Rate limit exceeded', 'status' => 429];
            }
            if ($response->successful()) {
                $data = $response->json();
                $coinData = collect($data['data'])->first();
                $quotes = $coinData['quotes'] ?? [];
                if (empty($quotes)) {
                    Log::warning('CoinMarketCap returned no quotes', ['symbol' => $mappedSymbol]);
                    return ['success' => false, 'error' => 'No chart data available', 'status' => 200];
                }
                $prices = collect($quotes)->map(function ($item) {
                    $timestamp = strtotime($item['timestamp']);
                    $price = $item['quote']['USD']['price'] ?? 0.0;
                    return [
                        $timestamp * 1000,
                        $price
                    ];
                })->values()->toArray();
                return [
                    'success' => true,
                    'data' => ['prices' => $prices],
                    'provider' => 'coinmarketcap'
                ];
            }
            return [
                'success' => false,
                'error' => 'Failed to fetch from CoinMarketCap',
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
                        'gwei' => (float)$safeGasPrice,
                        'time' => '~3 min',
                        'usd' => round($gweiToUsd($safeGasPrice), 2),
                    ],
                    'medium' => [
                        'gwei' => (float)$proposeGasPrice,
                        'time' => '~1 min',
                        'usd' => round($gweiToUsd($proposeGasPrice), 2),
                    ],
                    'high' => [
                        'gwei' => (float)$fastGasPrice,
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
            'low' => ['gwei' => 0.144273214, 'time' => '~3 min', 'usd' => 3.50],
            'medium' => ['gwei' => 0.144294078, 'time' => '~1 min', 'usd' => 4.90],
            'high' => ['gwei' => 0.158723485, 'time' => '~15 sec', 'usd' => 7.00],
        ];
    }

    private function fetchEthPrice(): float
    {
        $cacheKey = 'eth_price';
        $cached = Cache::get($cacheKey);
        if ($cached && is_numeric($cached) && $cached > 0) {
            return (float)$cached;
        }
        $price = $this->fetchPriceDataWithProviderFallback();
        if ($price > 0.0) {
            Cache::put($cacheKey, $price, self::CACHE_TTL['PRICE_DATA']);
        } else {
            Log::warning('Using fallback ETH price');
            $price = 2000.0;
        }
        return $price;
    }

    private function fetchPriceDataWithProviderFallback(): float
    {
        foreach (self::API_PROVIDERS['price'] as $provider) {
            $price = $this->fetchPriceDataWithRetry($provider);
            if ($price > 0.0) {
                return $price;
            }
            Log::warning("Failed to fetch price data from $provider after retries, trying next provider", [
                'symbol' => 'ethereum',
            ]);
        }
        return 0.0;
    }

    private function fetchPriceDataWithRetry(string $provider): float
    {
        $maxRetries = self::MAX_RETRIES;
        $rateLimitRetries = 0;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $result = $this->fetchPriceDataFromProvider($provider);
            $price = $result['price'] ?? 0.0;
            if ($price > 0.0) {
                return $price;
            }
            if (($result['status'] ?? 0) === 429) {
                $rateLimitRetries++;
                if ($rateLimitRetries > self::RATE_LIMIT_MAX_RETRIES) {
                    Log::warning("Rate limit retry limit exceeded for $provider price", [
                        'symbol' => 'ethereum',
                    ]);
                    return 0.0;
                }
                sleep(self::RATE_LIMIT_RETRY_DELAY);
                continue;
            }
            if ($attempt < $maxRetries - 1) {
                $delay = self::RETRY_BASE_DELAY * pow(2, $attempt);
                sleep($delay);
            }
        }
        return 0.0;
    }

    private function fetchPriceDataFromProvider(string $provider): array
    {
        try {
            return match ($provider) {
                'cryptocompare' => $this->fetchPriceDataFromCryptoCompare(),
                'coinmarketcap' => $this->fetchPriceDataFromCoinMarketCap(),
                'coingecko' => $this->fetchPriceDataFromCoinGeckoSimple(),
                default => ['price' => 0.0, 'status' => 501],
            };
        } catch (Throwable $e) {
            Log::error("Exception fetching price data from $provider", [
                'symbol' => 'ethereum',
                'error' => $e->getMessage()
            ]);
            return ['price' => 0.0, 'status' => 500];
        }
    }

    /**
     * @throws ConnectionException
     */
    private function fetchPriceDataFromCryptoCompare(): array
    {
        $mappedSymbol = $this->getMappedSymbol('ethereum', 'cryptocompare');
        $apiKey = config('services.cryptocompare.key');
        if (!$apiKey) {
            Log::warning('CryptoCompare API key not configured, skipping primary price provider.');
            return ['price' => 0.0, 'status' => 401];
        }
        $url = "https://min-api.cryptocompare.com/data/price?fsym=$mappedSymbol&tsyms=USD&api_key=$apiKey";
        $response = Http::timeout(10)->get($url);
        if ($response->status() === 429) {
            return ['price' => 0.0, 'status' => 429];
        }
        if ($response->successful()) {
            $data = $response->json();
            $price = $data['USD'] ?? 0.0;
            if (isset($data['Response']) && $data['Response'] === 'Error') {
                Log::warning('CryptoCompare error response', ['message' => $data['Message'] ?? '']);
                return ['price' => 0.0, 'status' => 400];
            }
            return ['price' => (float)$price, 'status' => 200];
        }
        return ['price' => 0.0, 'status' => $response->status()];
    }

    /**
     * @throws ConnectionException
     */
    private function fetchPriceDataFromCoinMarketCap(): array
    {
        $mappedSymbol = strtoupper($this->getMappedSymbol('ethereum', 'coinmarketcap'));
        $apiKey = config('services.coinmarketcap.key');
        if (!$apiKey) {
            Log::warning('CoinMarketCap API key not configured, skipping secondary price provider.');
            return ['price' => 0.0, 'status' => 401];
        }
        $url = "https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest";
        $response = Http::timeout(10)
            ->withHeaders(['X-CMC_PRO_API_KEY' => $apiKey])
            ->get($url, ['symbol' => $mappedSymbol, 'convert' => 'USD']);
        if ($response->status() === 429) {
            return ['price' => 0.0, 'status' => 429];
        }
        if ($response->successful()) {
            $data = $response->json();
            $price = $data['data'][$mappedSymbol]['quote']['USD']['price'] ?? 0.0;
            if (!$price && !empty($data['status']['error_message'])) {
                Log::warning('CoinMarketCap error response', ['message' => $data['status']['error_message']]);
            }
            return ['price' => (float)$price, 'status' => 200];
        }
        return ['price' => 0.0, 'status' => $response->status()];
    }

    /**
     * @throws ConnectionException
     */
    private function fetchPriceDataFromCoinGeckoSimple(): array
    {
        $mappedSymbol = $this->getMappedSymbol('ethereum', 'coingecko');
        $apiKey = config('services.coingecko.key', '');
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=$mappedSymbol&vs_currencies=usd";
        if ($apiKey) {
            $url .= '&x_cg_api_key=' . $apiKey;
        }
        $response = Http::timeout(10)->get($url);
        if ($response->status() === 429) {
            return ['price' => 0.0, 'status' => 429];
        }
        if ($response->successful()) {
            $data = $response->json();
            $price = $data[$mappedSymbol]['usd'] ?? 0.0;
            return ['price' => (float)$price, 'status' => 200];
        }
        return ['price' => 0.0, 'status' => $response->status()];
    }

    private function fetchCoinGeckoMarketData(array $coinIds): array
    {
        if (empty($coinIds)) {
            return [];
        }
        $idString = implode('_', $coinIds);
        $hashedIds = sha1($idString);
        $cacheKey = "coinGeckoSpecificCoins_" . $hashedIds;
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
        $ids = implode(',', $coinIds);
        $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=$ids&order=market_cap_desc&sparkline=false";
        try {
            $rawData = $this->fetchData(
                'temp_coingecko_market_data',
                $url,
                self::CACHE_TTL['PRICE_DATA'],
                'Failed to fetch CoinGecko market data for specific IDs'
            );
            if (!empty($rawData)) {
                return $this->transformCoinGeckoMarketData($rawData);
            }
            return [];
        } catch (Throwable $e) {
            Log::warning('CoinGecko market data fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function transformCoinGeckoMarketData(array $rawData): array
    {
        return collect($rawData)->map(function ($coin) {
            return [
                'id' => $coin['id'] ?? null,
                'symbol' => $coin['symbol'] ?? '',
                'name' => $coin['name'] ?? '',
                'image' => $coin['image'] ?? asset('assets/images/crypto.png'),
                'current_price' => $coin['current_price'] ?? 0.0,
                'market_cap' => $coin['market_cap'] ?? 0,
                'price_change_24h' => $coin['price_change_24h'] ?? 0,
                'price_change_percentage_24h' => $coin['price_change_percentage_24h'] ?? 0.0,
                'total_volume' => $coin['total_volume'] ?? 0.0,
            ];
        })->filter(fn($coin) => $coin['id'] !== null)->values()->toArray();
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
                    $quotes = $data['quotes']['USD'] ?? [];
                    $results[] = [
                        'id' => $coinId,
                        'symbol' => $data['symbol'] ?? '',
                        'name' => $data['name'] ?? '',
                        'image' => asset('assets/images/crypto.png'),
                        'current_price' => $quotes['price'] ?? 0.0,
                        'market_cap' => $quotes['market_cap'] ?? 0,
                        'price_change_24h' => 0.0,
                        'price_change_percentage_24h' => $quotes['percent_change_24h'] ?? 0.0,
                        'total_volume' => $quotes['volume_24h'] ?? 0.0,
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
        $ttl = self::CACHE_TTL['WALLET_DATA'];
        if ($apiKey = config('services.cryptocompare.key')) {
            $url .= "?api_key=" . $apiKey;
        }
        $cachedData = Cache::remember($cacheKey, $ttl, function () use ($url) {
            $response = $this->fetchFromAPIWithRetry($url);
            if ($response['error'] ?? false) {
                Log::warning("Failed to fetch CryptoCompare wallets data for caching.");
                return ['Data' => [], 'Message' => 'API fetch failed', 'Type' => 0];
            }
            $data = $response['data'] ?? [];
            if (isset($data['Data']) && is_array($data['Data'])) {
                $reducedData = $this->transformWalletData($data['Data']);
                return [
                    'Data' => $reducedData,
                    'Message' => $data['Message'] ?? ($data['message'] ?? ''),
                    'Type' => $data['Type'] ?? 100,
                ];
            }
            return ['Data' => [], 'Message' => $data['Message'] ?? ($data['message'] ?? 'Invalid API response'), 'Type' => 0];
        });
        return [
            'Data' => isset($cachedData['Data']) ? $this->sortWalletData($cachedData['Data']) : [],
            'Message' => $cachedData['Message'] ?? '',
        ];
    }

    private function transformWalletData(array $wallets): array
    {
        return array_map(function (array $wallet) {
            return [
                'Id' => $wallet['Id'] ?? null,
                'Name' => $wallet['Name'] ?? 'Unknown Wallet',
                'LogoUrl' => $wallet['LogoUrl'] ?? null,
            ];
        }, $wallets);
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
        if (!empty($data) && !str_contains($cacheKey, 'coinGeckoSpecificCoins_') && !str_contains($cacheKey, 'raw_coingecko_no_pagination_temp')) {
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
                Log::warning("Rate limit hit, retrying utility fetch", ['url' => $apiUrl]);
                sleep(self::RATE_LIMIT_RETRY_DELAY);
                $retries++;
                continue;
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
            $urlWithKey = $apiUrl;
            $coingeckoKey = config('services.coingecko.key', '');
            if ($coingeckoKey && str_contains($apiUrl, 'coingecko.com') && !str_contains($apiUrl, 'x_cg_api_key=')) {
                $urlWithKey = $apiUrl . (str_contains($apiUrl, '?') ? '&' : '?') . 'x_cg_api_key=' . $coingeckoKey;
            }
            $cryptoCompareKey = config('services.cryptocompare.key');
            if ($cryptoCompareKey && str_contains($apiUrl, 'cryptocompare.com') && !str_contains($apiUrl, 'api_key=')) {
                $urlWithKey = $apiUrl . (str_contains($apiUrl, '?') ? '&' : '?') . 'api_key=' . $cryptoCompareKey;
            }
            $headers = [];
            $response = Http::timeout(20)->withHeaders($headers)->get($urlWithKey);
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
