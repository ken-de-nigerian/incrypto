<?php
namespace App\Services;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;
class GatewayHandlerService
{
    /**
     * Cache lifetimes in seconds
     */
    private const CACHE_TTL = [
        'PRICE_DATA' => 300, // 5 minutes
        'WALLET_DATA' => 43200, // 12 hours
        'CHART_DATA' => 300, // 5 minutes
        'CRYPTOS_LIST' => 3600, // 1 hour
        'FALLBACK_CHART_DATA' => 1800, // 30 minutes for fallback data
    ];

    /**
     * Retry logic constants
     */
    private const MAX_RETRIES = 3;
    private const RETRY_BASE_DELAY = 1;
    private const RATE_LIMIT_MAX_RETRIES = 2;
    private const RATE_LIMIT_RETRY_DELAY = 5;

    /**
     * Circuit Breaker constants
     */
    private const CIRCUIT_BREAKER_TIMEOUT = 60; // seconds to keep circuit open
    private const FAILURE_THRESHOLD = 5; // consecutive failures to open circuit

    /**
     * Global Request Queuing (Token Bucket) constants
     * Adjust based on API rate limits
     */
    private const MAX_TOKENS = 100; // maximum tokens in bucket
    private const REFILL_TOKENS_PER_MINUTE = 100; // refill rate per minute
    private const QUEUE_CHECK_INTERVAL = 1; // seconds to wait before checking token again

    /**
     * Fallback chart data constants
     */
    private const FALLBACK_CHART_BARS = 120; // Number of candles to generate
    private const FALLBACK_VOLATILITY_FACTORS = [
        'forex' => 0.002, // 0.2% average daily volatility
        'stock' => 0.015, // 1.5% average daily volatility
        'crypto' => 0.035, // 3.5% average daily volatility
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
     * Fetch historical chart data for a trading pair
     */
    public function fetchTradeChartData(string $symbol, string $category = 'forex'): array
    {
        // Validate category
        $supportedCategories = ['forex', 'stock', 'crypto'];
        if (!in_array($category, $supportedCategories)) {
            $error = "Unsupported category: $category. Supported: " . implode(', ', $supportedCategories);
            Log::warning($error, ['method' => __METHOD__, 'symbol' => $symbol, 'category' => $category]);
            return ['success' => false, 'error' => $error];
        }

        // Fetch pairs based on category
        $allPairs = match($category) {
            'forex' => $this->getAllForexPairs(),
            'stock' => $this->getAllStocksPairs(),
            'crypto' => $this->getAllCryptoPairs(),
            default => $this->getAllForexPairs()
        };

        $pairData = collect($allPairs)->firstWhere('symbol', $symbol);

        if (!$pairData) {
            $error = 'Pair not found';
            Log::warning($error, ['method' => __METHOD__, 'symbol' => $symbol, 'category' => $category]);
            return ['success' => false, 'error' => $error];
        }

        // Check for cached fallback data first (in case API is consistently failing)
        $fallbackCacheKey = "fallback_chart_{$category}_$symbol";

        // Only try API if we don't have polygon data or API key
        if (!isset($pairData['polygon'])) {
            Log::info("No polygon data available for $symbol, using fallback", [
                'method' => __METHOD__,
                'symbol' => $symbol,
                'category' => $category
            ]);
            return $this->generateFallbackChartData($symbol, $category, $pairData, $fallbackCacheKey);
        }

        $apiKey = config('services.massive.key');
        if (!$apiKey) {
            Log::warning('API key not configured, using fallback', [
                'method' => __METHOD__,
                'service' => 'massive'
            ]);
            return $this->generateFallbackChartData($symbol, $category, $pairData, $fallbackCacheKey);
        }

        // Dynamic cache key
        $cacheKey = "chart_{$category}_$symbol";

        $cached = Cache::get($cacheKey);
        if ($cached && ($cached['success'] ?? false)) {
            return $cached;
        }

        try {

            $to = now()->format('Y-m-d');
            $from = now()->subYears(5)->format('Y-m-d');
            $encodedApiKey = urlencode($apiKey);
            $limit = 120;

            $baseUrl = "https://api.massive.com/v2/aggs/ticker/{$pairData['polygon']}/range/1/day/$from/$to?adjusted=true&sort=desc&limit=$limit&apiKey=$encodedApiKey";

            // Initial fetch with resilience
            $responseData = $this->makeResilientRequest('massive', $baseUrl, [], 30);
            $data = $responseData['json'];

            $allResults = $data['results'] ?? [];

            // If no results, use fallback
            if (empty($allResults)) {
                Log::warning("No results from API, using fallback", [
                    'method' => __METHOD__,
                    'symbol' => $symbol,
                    'category' => $category
                ]);
                return $this->generateFallbackChartData($symbol, $category, $pairData, $fallbackCacheKey);
            }

            // Pagination url
            $nextUrl = $data['next_url'] ?? null;

            // Create a proxied next_url
            $proxiedNextUrl = null;

            if ($nextUrl) {

                // Parse the full next_url
                $parsedUrl = parse_url($nextUrl);
                parse_str($parsedUrl['query'] ?? '', $queryParams);

                // Extract all necessary parameters
                $cursor = $queryParams['cursor'] ?? null;

                // Extract the new 'from' timestamp from the path
                $pathParts = explode('/', $parsedUrl['path'] ?? '');
                $fromTimestamp = $pathParts[count($pathParts) - 2] ?? null;
                $toDate = $pathParts[count($pathParts) - 1] ?? null;

                if ($cursor && $fromTimestamp) {
                    $proxiedNextUrl = route('user.trade.chart.paginate', [
                        'symbol' => $symbol,
                        'category' => $category,
                        'cursor' => $cursor,
                        'from' => $fromTimestamp,
                        'to' => $toDate
                    ]);
                }
            }

            $resultsCollection = collect($allResults);

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
            })->reverse()->values()->toArray();

            $latestResult = $resultsCollection->last();

            $result = [
                'success' => true,
                'data' => $chartData,
                'next_url' => $proxiedNextUrl,
                'symbol' => $symbol,
                'timeframe' => '1D',
                'total_bars' => count($chartData),
                'high' => $latestResult['h'] ?? 0,
                'low' => $latestResult['l'] ?? 0,
                'volume' => $latestResult['v'] ?? 0,
                'is_fallback' => false,
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL['CHART_DATA']);

            return $result;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error("Failed to fetch trade chart data from API, using fallback", [
                'method' => __METHOD__,
                'symbol' => $symbol,
                'category' => $category,
                'error' => $error,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->generateFallbackChartData($symbol, $category, $pairData, $fallbackCacheKey);
        }
    }

    /**
     * Generate fallback chart data when API fails
     */
    private function generateFallbackChartData(string $symbol, string $category, array $pairData, string $cacheKey): array
    {
        // Check if we have cached fallback data
        $cached = Cache::get($cacheKey);
        if ($cached && ($cached['success'] ?? false)) {
            return $cached;
        }

        try {
            // Get the current price from pair data or use a default
            $currentPrice = $pairData['price'] ?? $this->getDefaultPriceForPair($symbol, $category);

            if (!$currentPrice || $currentPrice <= 0) {
                $currentPrice = $this->getDefaultPriceForPair($symbol, $category);
            }

            // Get a volatility factor for this category
            $volatilityFactor = self::FALLBACK_VOLATILITY_FACTORS[$category] ?? 0.015;

            // Generate chart data
            $chartData = $this->generateSyntheticChartData(
                $currentPrice,
                $volatilityFactor,
                $category
            );

            // Get latest candle for stats
            $latestCandle = $chartData[count($chartData) - 1] ?? null;

            $result = [
                'success' => true,
                'data' => $chartData,
                'next_url' => null, // No pagination for fallback data
                'symbol' => $symbol,
                'timeframe' => '1D',
                'total_bars' => count($chartData),
                'high' => $latestCandle['high'] ?? $currentPrice,
                'low' => $latestCandle['low'] ?? $currentPrice,
                'volume' => $latestCandle['volume'] ?? 100000,
                'is_fallback' => true,
                'fallback_message' => 'Using synthetic data due to API unavailability'
            ];

            // Cache fallback data with shorter TTL
            Cache::put($cacheKey, $result, self::CACHE_TTL['FALLBACK_CHART_DATA']);

            return $result;

        } catch (Throwable $e) {
            Log::error("Failed to generate fallback chart data", [
                'method' => __METHOD__,
                'symbol' => $symbol,
                'category' => $category,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate fallback chart data',
                'is_fallback' => true
            ];
        }
    }

    /**
     * Generate synthetic chart data with realistic price movements
     */
    private function generateSyntheticChartData(
        float $currentPrice,
        float $volatilityFactor,
        string $category
    ): array {
        $chartData = [];
        $now = now();

        // Generate seed based on the current day to maintain consistency
        $seed = (int) $now->format('Ymd');
        mt_srand($seed);

        // Calculate base volume based on category
        $baseVolume = match($category) {
            'forex' => 150000,
            'stock' => 1000000,
            'crypto' => 500000,
            default => 150000
        };

        // Calculate starting price (work backwards from current price)
        $startingPrice = $currentPrice;
        $priceHistory = [$startingPrice];

        // First, calculate all prices working backwards to get the starting point
        for ($i = 0; $i < self::FALLBACK_CHART_BARS - 1; $i++) {
            $dailyChange = $this->generateRandomChange($volatilityFactor);
            $previousPrice = $startingPrice / (1 + $dailyChange);
            array_unshift($priceHistory, $previousPrice);
            $startingPrice = $previousPrice;
        }

        // Now generate candles forward in time with proper OHLC
        for ($i = 0; $i < self::FALLBACK_CHART_BARS; $i++) {
            // Calculate timestamp
            $timestamp = $now->copy()->subDays(self::FALLBACK_CHART_BARS - 1 - $i)->startOfDay()->timestamp;

            $open = $priceHistory[$i];

            // Determine close price
            if ($i < self::FALLBACK_CHART_BARS - 1) {
                // For all candles except the last, close should trend toward the next open
                $nextOpen = $priceHistory[$i + 1];
                $closeVariation = $this->generateRandomChange($volatilityFactor * 0.3);
                $close = $open + (($nextOpen - $open) * 0.7) + ($open * $closeVariation);
            } else {
                // Last candle closes at current price
                $close = $currentPrice;
            }

            // Generate intraday high and low
            // High should be above both open and close
            // Low should be below both open and close
            $maxPrice = max($open, $close);
            $minPrice = min($open, $close);

            // Add some wick to high and low
            $highWickPercent = abs($this->generateRandomChange($volatilityFactor * 0.4));
            $lowWickPercent = abs($this->generateRandomChange($volatilityFactor * 0.4));

            $high = $maxPrice * (1 + $highWickPercent);
            $low = $minPrice * (1 - $lowWickPercent);

            // Ensure OHLC relationships are valid
            $high = max($high, $open, $close);
            $low = min($low, $open, $close);

            // Ensure high >= low (safety check)
            if ($high < $low) {
                $temp = $high;
                $high = $low;
                $low = $temp;
            }

            // Generate volume with some randomness
            $volumeVariation = 0.7 + (mt_rand() / mt_getrandmax()) * 0.6; // 0.7 to 1.3
            $volume = (int) ($baseVolume * $volumeVariation);

            // Get precision based on category
            $precision = $this->getPricePrecisionForCategory($category, $currentPrice);

            $chartData[] = [
                'time' => $timestamp,
                'open' => round($open, $precision),
                'high' => round($high, $precision),
                'low' => round($low, $precision),
                'close' => round($close, $precision),
                'volume' => $volume
            ];
        }

        return $chartData;
    }

    /**
     * Get appropriate price precision based on category and price level
     */
    private function getPricePrecisionForCategory(string $category, float $price): int
    {
        return match($category) {
            'forex' => 5,
            'stock' => $price < 10 ? 4 : 2,
            'crypto' => $price > 1000 ? 2 : ($price > 10 ? 3 : 5),
            default => 5
        };
    }

    /**
     * Generate a random price change using Box-Muller transform for normal distribution
     */
    private function generateRandomChange(float $volatility): float
    {
        // Box-Muller transform to generate normally distributed random numbers
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();

        // Avoid log(0)
        $u1 = max($u1, 0.0001);

        $z0 = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);

        return $z0 * $volatility;
    }

    /**
     * Get the default price for a pair when no price data is available
     */
    private function getDefaultPriceForPair(string $symbol, string $category): float
    {
        // Default prices based on common pairs
        $defaultPrices = [
            // Forex
            'EUR/USD' => 1.08,
            'GBP/USD' => 1.27,
            'USD/JPY' => 149.50,
            'AUD/USD' => 0.66,
            'USD/CAD' => 1.36,
            'USD/CHF' => 0.88,
            'NZD/USD' => 0.61,
            'EUR/GBP' => 0.85,
            'EUR/JPY' => 161.50,
            'GBP/JPY' => 190.00,

            // Stocks (approximate prices)
            'AAPL' => 180.00,
            'MSFT' => 380.00,
            'GOOGL' => 140.00,
            'AMZN' => 175.00,
            'TSLA' => 250.00,
            'META' => 485.00,
            'NVDA' => 875.00,
            'JPM' => 190.00,
            'V' => 280.00,
            'WMT' => 170.00,

            // Crypto
            'BTC/USD' => 95000.00,
            'ETH/USD' => 3300.00,
            'BNB/USD' => 650.00,
            'XRP/USD' => 2.30,
            'ADA/USD' => 0.95,
            'SOL/USD' => 190.00,
            'DOT/USD' => 7.20,
            'DOGE/USD' => 0.32,
            'MATIC/USD' => 0.48,
            'LTC/USD' => 105.00,
        ];

        // Return a specific price if available, otherwise use category defaults
        if (isset($defaultPrices[$symbol])) {
            return $defaultPrices[$symbol];
        }

        // Category-based defaults
        return match($category) {
            'forex' => 1.00,
            'stock' => 100.00,
            'crypto' => 1000.00,
            default => 1.00
        };
    }

    /**
     * Fetch paginated chart data for a trading pair
     */
    public function fetchPaginatedChartData(string $symbol, string $category, string $cursor, ?string $from = null, ?string $to = null): array
    {
        try {

            // Validate category
            $supportedCategories = ['forex', 'stock', 'crypto'];
            if (!in_array($category, $supportedCategories)) {
                $error = "Unsupported category: $category";
                Log::warning($error, ['method' => __METHOD__, 'symbol' => $symbol, 'category' => $category]);
                return ['success' => false, 'error' => $error];
            }

            // Get pair data
            $allPairs = match($category) {
                'forex' => $this->getAllForexPairs(),
                'stock' => $this->getAllStocksPairs(),
                'crypto' => $this->getAllCryptoPairs(),
                default => $this->getAllForexPairs()
            };

            $pairData = collect($allPairs)->firstWhere('symbol', $symbol);

            if (!$pairData || !isset($pairData['polygon'])) {
                // For pagination, if no polygon data, return empty (fallback doesn't support pagination)
                Log::info("No polygon data for pagination request", [
                    'method' => __METHOD__,
                    'symbol' => $symbol,
                    'category' => $category
                ]);
                return [
                    'success' => true,
                    'data' => [],
                    'next_url' => null,
                    'symbol' => $symbol,
                    'timeframe' => '1D',
                    'total_bars' => 0,
                    'message' => 'Pagination not available for this pair'
                ];
            }

            $apiKey = config('services.massive.key');
            if (!$apiKey) {
                return [
                    'success' => true,
                    'data' => [],
                    'next_url' => null,
                    'symbol' => $symbol,
                    'timeframe' => '1D',
                    'total_bars' => 0,
                    'message' => 'API key not configured'
                ];
            }

            // Use provided from/to or default to current date range
            if (!$from) {
                $from = now()->subYears(5)->startOfDay()->timestamp * 1000;
            }
            if (!$to) {
                $to = now()->format('Y-m-d');
            }

            $encodedApiKey = urlencode($apiKey);
            $limit = 120;
            $url = "https://api.massive.com/v2/aggs/ticker/{$pairData['polygon']}/range/1/day/$from/$to?adjusted=true&sort=desc&limit=$limit&cursor=$cursor&apiKey=$encodedApiKey";

            // Fetch with resilience
            $responseData = $this->makeResilientRequest('massive', $url, [], 30);
            $data = $responseData['json'];

            $allResults = $data['results'] ?? [];

            // Get the next cursor
            $nextUrl = $data['next_url'] ?? null;
            $proxiedNextUrl = null;

            if ($nextUrl) {

                // Parse the next_url
                $parsedUrl = parse_url($nextUrl);
                parse_str($parsedUrl['query'] ?? '', $queryParams);
                $newCursor = $queryParams['cursor'] ?? null;

                // Extract the new 'from' timestamp from the path
                $pathParts = explode('/', $parsedUrl['path'] ?? '');
                $newFromTimestamp = $pathParts[count($pathParts) - 2] ?? null;
                $newToDate = $pathParts[count($pathParts) - 1] ?? null;

                if ($newCursor && $newFromTimestamp) {
                    $proxiedNextUrl = route('user.trade.chart.paginate', [
                        'symbol' => $symbol,
                        'category' => $category,
                        'cursor' => $newCursor,
                        'from' => $newFromTimestamp,
                        'to' => $newToDate
                    ]);
                } else {
                    Log::warning('Could not extract pagination parameters from next_url');
                }
            }

            // Transform to frontend format
            $chartData = collect($allResults)->map(function ($candle) {
                return [
                    'time' => $candle['t'] / 1000,  // Convert milliseconds to seconds
                    'open' => $candle['o'],
                    'high' => $candle['h'],
                    'low' => $candle['l'],
                    'close' => $candle['c'],
                    'volume' => $candle['v'] ?? 0
                ];
            })->reverse()->values()->toArray();

            return [
                'success' => true,
                'data' => $chartData,
                'next_url' => $proxiedNextUrl,
                'symbol' => $symbol,
                'timeframe' => '1D',
                'total_bars' => count($chartData)
            ];

        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error("Failed to fetch paginated chart data", [
                'method' => __METHOD__,
                'symbol' => $symbol,
                'category' => $category,
                'cursor' => $cursor,
                'error' => $error,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $error
            ];
        }
    }

    /**
     * Make resilient API request with circuit breaker, token bucket, and retry logic.
     * @throws Exception
     */
    private function makeResilientRequest(string $provider, string $url, array $params = [], int $timeout = 10): array
    {
        $headers = [];
        // Build full URL with params
        if (!empty($params)) {
            $url .= (!str_contains($url, '?') ? '?' : '&') . http_build_query($params);
        }

        // Add API keys based on provider
        $apiKey = config("services.$provider.key");
        if ($apiKey) {
            switch ($provider) {
                case 'coingecko':
                    if (!str_contains($url, 'x_cg_api_key=')) {
                        $url .= (!str_contains($url, '?') ? '?' : '&') . 'x_cg_api_key=' . $apiKey;
                    }
                    break;
                case 'cryptocompare':
                    if (!str_contains($url, 'api_key=')) {
                        $url .= (!str_contains($url, '?') ? '?' : '&') . 'api_key=' . $apiKey;
                    }
                    break;
                case 'coinmarketcap':
                    $headers['X-CMC_PRO_API_KEY'] = $apiKey;
                    break;
                // For massive, etherscan, coinpaprika: assume URL has key or no key needed
                default:
                    // No additional action
            }
        }

        // Check circuit breaker
        if ($this->isCircuitOpen($provider)) {
            $error = "Circuit breaker is open for $provider: API temporarily unavailable";
            Log::warning($error, ['method' => __METHOD__, 'provider' => $provider]);
            throw new Exception($error);
        }

        $maxRetries = self::MAX_RETRIES;
        $rateLimitRetries = 0;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            // Global request queuing: wait for token if necessary
            if (!$this->consumeToken($provider)) {
                sleep(self::QUEUE_CHECK_INTERVAL);
                continue;
            }

            try {
                $response = Http::timeout($timeout)->withHeaders($headers)->get($url);

                if ($response->status() === 429) {
                    $this->recordFailure($provider);
                    $rateLimitRetries++;
                    if ($rateLimitRetries > self::RATE_LIMIT_MAX_RETRIES) {
                        $error = "Rate limit exceeded after maximum retries for $provider";
                        Log::warning($error, ['method' => __METHOD__, 'provider' => $provider, 'url' => $url]);
                        throw new Exception($error);
                    }
                    sleep(self::RATE_LIMIT_RETRY_DELAY);
                    continue;
                }

                if ($response->successful()) {
                    $this->recordSuccess($provider);
                    return [
                        'status' => $response->status(),
                        'json' => $response->json()
                    ];
                } else {
                    $this->recordFailure($provider);
                    $error = "API returned HTTP {$response->status()} for $provider";
                    Log::error($error, ['method' => __METHOD__, 'provider' => $provider, 'url' => $url]);
                    throw new Exception($error);
                }
            } catch (ConnectionException|Throwable $e) {
                $this->recordFailure($provider);
                Log::error("Request failed during execution", [
                    'method' => __METHOD__,
                    'provider' => $provider,
                    'url' => $url,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Exponential backoff for non-rate-limit errors
            if ($attempt < $maxRetries - 1) {
                $delay = self::RETRY_BASE_DELAY * pow(2, $attempt);
                sleep($delay);
            }
        }

        $error = "Max retries exceeded for $provider API request";
        Log::error($error, ['method' => __METHOD__, 'provider' => $provider, 'url' => $url]);
        throw new Exception($error);
    }

    /**
     * Circuit Breaker: Check if circuit is open for a provider.
     */
    private function isCircuitOpen(string $provider): bool
    {
        $state = $this->getCircuitState($provider);

        if ($state['state'] === 'open') {
            $lastFailure = $state['last_failure'] ? Carbon::parse($state['last_failure']) : now()->subSeconds(self::CIRCUIT_BREAKER_TIMEOUT + 1);
            $timeoutExpired = now()->diffInSeconds($lastFailure) > self::CIRCUIT_BREAKER_TIMEOUT;
            if ($timeoutExpired) {
                // Transition to half-open
                $state['state'] = 'half-open';
                $state['failure_count'] = 0;
                $this->setCircuitState($provider, $state);
                return false; // Allow request in half-open state
            }
            return true;
        }

        return false;
    }

    /**
     * Circuit Breaker: Record a failure for a provider.
     */
    private function recordFailure(string $provider): void
    {
        $state = $this->getCircuitState($provider);
        $state['failure_count'] = ($state['failure_count'] ?? 0) + 1;
        $state['last_failure'] = now()->toDateTimeString();

        if ($state['state'] === 'half-open') {
            $state['state'] = 'open';
            $state['failure_count'] = 1;
        } elseif ($state['failure_count'] >= self::FAILURE_THRESHOLD) {
            $state['state'] = 'open';
        }

        $this->setCircuitState($provider, $state);
    }

    /**
     * Circuit Breaker: Record a success for a provider.
     */
    private function recordSuccess(string $provider): void
    {
        $state = $this->getCircuitState($provider);
        $state['state'] = 'closed';
        $state['failure_count'] = 0;
        $this->setCircuitState($provider, $state);
    }

    /**
     * Circuit Breaker: Get the current state for a provider from cache.
     */
    private function getCircuitState(string $provider): array
    {
        $key = "circuit_$provider";
        return Cache::get($key, [
            'state' => 'closed',
            'failure_count' => 0,
            'last_failure' => null
        ]);
    }

    /**
     * Circuit Breaker: Set state in cache for a provider.
     */
    private function setCircuitState(string $provider, array $state): void
    {
        $key = "circuit_$provider";
        Cache::put($key, $state, self::CIRCUIT_BREAKER_TIMEOUT);
    }

    /**
     * Global Request Queuing: Token bucket implementation for a provider.
     * Returns true if token consumed, false if queued (wait needed).
     */
    private function consumeToken(string $provider): bool
    {
        $bucket = $this->refillBucket($provider);

        if ($bucket['tokens'] < 1) {
            return false;
        }

        $bucket['tokens']--;
        $bucketKey = "token_bucket_$provider";
        Cache::put($bucketKey, $bucket, 60); // Cache for 1 minute

        return true;
    }

    /**
     * Refill the token bucket for a provider based on time elapsed.
     */
    private function refillBucket(string $provider): array
    {
        $bucketKey = "token_bucket_$provider";
        $bucket = Cache::get($bucketKey, [
            'tokens' => (float) self::MAX_TOKENS,
            'last_refill' => now()
        ]);

        $now = now();
        $secondsElapsed = $now->diffInSeconds($bucket['last_refill']);
        $minutesElapsed = $secondsElapsed / 60.0;
        $refillAmount = $minutesElapsed * self::REFILL_TOKENS_PER_MINUTE;

        $bucket['tokens'] = min(self::MAX_TOKENS, $bucket['tokens'] + $refillAmount);
        $bucket['last_refill'] = $now;

        return $bucket;
    }

    /**
     * Get all available forex pairs with flag image URLs included.
     */
    public function getAllForexPairs(): array
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

            // Get country codes
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
     * Get all available stocks with image URLs included.
     */
    public function getAllStocksPairs(): array
    {
        return StocksImageService::getStocksWithImages();
    }

    /**
     * Get all available cryptos with image URLs included.
     */
    public function getAllCryptoPairs(): array
    {
        return CryptoImageService::getCryptosWithImages();
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
            Log::error("Failed to fetch gateways", [
                'method' => __METHOD__,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            $error = 'Days must be between 0.01 and 365';
            Log::warning($error, ['method' => __METHOD__, 'symbol' => $symbol, 'days' => $days]);
            return ['error' => $error];
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
            try {
                $result = $this->fetchChartDataWithRetry($provider, $symbol, $days);
                if ($result['success']) {
                    return $result;
                }
            } catch (Throwable $e) {
                Log::warning("Failed to fetch chart data from $provider after retries, trying next provider", [
                    'method' => __METHOD__,
                    'symbol' => $symbol,
                    'days' => $days,
                    'provider' => $provider,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $error = 'All API providers failed';
        Log::error($error, ['method' => __METHOD__, 'symbol' => $symbol, 'days' => $days]);
        return [
            'success' => false,
            'error' => $error,
            'message' => 'Unable to fetch chart data from any provider'
        ];
    }

    private function fetchChartDataWithRetry(string $provider, string $symbol, float $days): array
    {
        $maxRetries = self::MAX_RETRIES;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                return $this->fetchChartDataFromProvider($provider, $symbol, $days);
            } catch (Throwable) {
                if ($attempt < $maxRetries - 1) {
                    $delay = self::RETRY_BASE_DELAY * pow(2, $attempt);
                    sleep($delay);
                }
            }
        }
        $error = "Failed after " . self::MAX_RETRIES . " attempts";
        Log::warning($error, ['method' => __METHOD__, 'provider' => $provider, 'symbol' => $symbol, 'days' => $days]);
        return [
            'success' => false,
            'error' => $error
        ];
    }

    /**
     * @throws Exception
     */
    private function fetchChartDataFromProvider(string $provider, string $symbol, float $days): array
    {
        return match ($provider) {
            'coingecko' => $this->fetchChartDataFromCoinGecko($symbol, $days),
            'coinmarketcap' => $this->fetchChartDataFromCoinMarketCap($symbol, $days),
            'coinpaprika' => $this->fetchChartDataFromCoinPaprika($symbol, $days),
            default => ['success' => false, 'error' => 'Unknown provider'],
        };
    }

    /**
     * @throws Exception
     */
    private function fetchChartDataFromCoinGecko(string $symbol, float $days): array
    {
        try {
            $mappedSymbol = $this->getMappedSymbol($symbol, 'coingecko');
            $url = "https://api.coingecko.com/api/v3/coins/$mappedSymbol/market_chart";
            $params = [
                'vs_currency' => 'usd',
                'days' => $days,
            ];
            $responseData = $this->makeResilientRequest('coingecko', $url, $params);
            $data = $responseData['json'];
            if (!isset($data['prices']) || !is_array($data['prices'])) {
                throw new Exception('Invalid chart data structure received');
            }
            return ['success' => true, 'data' => $data, 'provider' => 'coingecko'];
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error("Failed to fetch chart data from CoinGecko", [
                'method' => __METHOD__,
                'symbol' => $symbol,
                'days' => $days,
                'error' => $error
            ]);
            return ['success' => false, 'error' => $error];
        }
    }

    private function fetchChartDataFromCoinMarketCap(string $symbol, float $days): array
    {
        $mappedSymbol = strtoupper($this->getMappedSymbol($symbol, 'coinmarketcap'));
        $apiKey = config('services.coinmarketcap.key');
        if (!$apiKey) {
            $error = 'API key missing';
            Log::error($error, ['method' => __METHOD__, 'provider' => 'coinmarketcap']);
            return ['success' => false, 'error' => $error];
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
            $params = [
                'symbol' => $mappedSymbol,
                'convert' => 'USD',
                'time_start' => $timeStart,
                'time_end' => $timeEnd,
                'interval' => $interval,
            ];
            $responseData = $this->makeResilientRequest('coinmarketcap', $url, $params);
            $data = $responseData['json'];
            $coinData = collect($data['data'])->first();
            $quotes = $coinData['quotes'] ?? [];
            if (empty($quotes)) {
                return ['success' => false, 'error' => 'No chart data available'];
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
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error("Failed to fetch chart data from CoinMarketCap", [
                'method' => __METHOD__,
                'symbol' => $symbol,
                'days' => $days,
                'error' => $error
            ]);
            return ['success' => false, 'error' => $error];
        }
    }

    private function fetchChartDataFromCoinPaprika(string $symbol, float $days): array
    {
        try {
            $mappedSymbol = $this->getMappedSymbol($symbol, 'coinpaprika');
            $interval = $days <= 1 ? '1h' : ($days <= 7 ? '6h' : '1d');
            $start = now()->subDays($days)->toIso8601String();
            $url = "https://api.coinpaprika.com/v1/tickers/$mappedSymbol/historical";
            $params = [
                'start' => $start,
                'interval' => $interval,
            ];
            $responseData = $this->makeResilientRequest('coinpaprika', $url, $params);
            $data = $responseData['json'];
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
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error("Failed to fetch chart data from CoinPaprika", [
                'method' => __METHOD__,
                'symbol' => $symbol,
                'days' => $days,
                'error' => $error
            ]);
            return ['success' => false, 'error' => $error];
        }
    }

    public function fetchGasPrices(string $chain = 'Ethereum'): array
    {
        return [
            'low' => ['gwei' => 0.08295024, 'time' => '~3 min', 'usd' => 3.50],
            'medium' => ['gwei' => 0.08295024, 'time' => '~1 min', 'usd' => 4.90],
            'high' => ['gwei' => 0.091245264, 'time' => '~15 sec', 'usd' => 7.00],
        ];
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
        try {
            $result = $this->fetchMarketDataFromCoinGeckoWithRetry($coinIds);
            if (!empty($result)) {
                Cache::put($cacheKey, $result, self::CACHE_TTL['PRICE_DATA']);
                return $result;
            }
        } catch (Throwable $e) {
            Log::warning('CoinGecko market data fetch failed, falling back to CoinPaprika', [
                'method' => __METHOD__,
                'coin_ids' => $coinIds,
                'error' => $e->getMessage()
            ]);
        }
        $fallbackResult = $this->fetchMarketDataFromCoinPaprika($coinIds);
        if (!empty($fallbackResult)) {
            Cache::put($cacheKey, $fallbackResult, self::CACHE_TTL['PRICE_DATA']);
        }
        return $fallbackResult;
    }

    private function fetchMarketDataFromCoinGeckoWithRetry(array $coinIds): array
    {
        $maxRetries = self::MAX_RETRIES;
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
                    if ($attempt < $maxRetries - 1) {
                        sleep(self::RATE_LIMIT_RETRY_DELAY);
                    }
                    continue;
                }
                if ($attempt < $maxRetries - 1) {
                    $delay = self::RETRY_BASE_DELAY * pow(2, $attempt);
                    sleep($delay);
                }
            }
        }
        Log::warning('CoinGecko market data fetch exhausted retries', ['method' => __METHOD__, 'coin_ids' => $coinIds]);
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
            Log::error("Failed to fetch market data from CoinGecko", [
                'method' => __METHOD__,
                'coin_ids' => $coinIds,
                'error' => $e->getMessage()
            ]);
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
                $url = "https://api.coinpaprika.com/v1/tickers/$mappedId";
                $responseData = $this->makeResilientRequest('coinpaprika', $url);
                $data = $responseData['json'];
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
            } catch (Throwable $e) {
                Log::warning("Failed to fetch $coinId from CoinPaprika", [
                    'method' => __METHOD__,
                    'coin_id' => $coinId,
                    'error' => $e->getMessage()
                ]);
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
            return [];
        }
        $coinData = $this->fetchCoinGeckoMarketData($coinIds);
        if (empty($coinData)) {
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
            $response = $this->fetchFromAPIWithRetry($url, 'cryptocompare');
            if ($response['error'] ?? false) {
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

    private function fetchData(string $cacheKey, string $apiUrl, int $ttl, string $errorContext): array
    {
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }
        $response = $this->fetchFromAPIWithRetry($apiUrl, 'coingecko');
        if ($response['error'] ?? false) {
            Log::warning($errorContext, ['method' => __METHOD__, 'provider' => 'coingecko', 'url' => $apiUrl]);
            return [];
        }
        $data = $response['data'] ?? [];
        if (!empty($data) && !str_contains($cacheKey, 'coinGeckoSpecificCoins_') && !str_contains($cacheKey, 'raw_coingecko_no_pagination_temp')) {
            Cache::put($cacheKey, $data, $ttl);
        }
        return $data;
    }

    private function fetchFromAPIWithRetry(string $apiUrl, string $provider): array
    {
        try {
            $responseData = $this->makeResilientRequest($provider, $apiUrl, [], 20);
            if ($responseData['status'] >= 200 && $responseData['status'] < 300) {
                return ['error' => false, 'data' => $responseData['json']];
            }
            return ['error' => true, 'status' => $responseData['status'], 'data' => []];
        } catch (Throwable $e) {
            Log::error("API fetch with retry failed", [
                'method' => __METHOD__,
                'provider' => $provider,
                'url' => $apiUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => true, 'status' => 500, 'data' => []];
        }
    }
}
