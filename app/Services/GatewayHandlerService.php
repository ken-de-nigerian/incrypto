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
        'CRYPTOS_LIST' => 3600,    // 1 hour
        'FOREX_PAIRS' => 300,      // 5 minutes
        'FOREX_QUOTE' => 30,       // 30 seconds for real-time quotes
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
     * Get a list of all available forex pairs (metadata only - no price data)
     */
    public function getAvailableForexPairs(): array
    {
        return $this->getAllPairs();
    }

    /**
     * Fetch single forex pair data on-demand
     */
    public function fetchSingleForexPair(string $symbol): ?array
    {
        $cacheKey = "forex_pair_data_$symbol";
        $ttl = self::CACHE_TTL['FOREX_PAIRS'];

        return Cache::remember($cacheKey, $ttl, function () use ($symbol) {
            $allPairs = $this->getAllPairs();
            $pairData = collect($allPairs)->firstWhere('symbol', $symbol);

            if (!$pairData) {
                Log::warning("Forex pair not found: $symbol");
                return null;
            }

            $apiKey = config('services.polygon.key');
            if (!$apiKey) {
                Log::warning('Polygon API key not configured');
                return $this->generateRealisticFallbackData($pairData);
            }

            $result = $this->fetchSinglePairWithBackoff($pairData, $apiKey);

            if ($result['success']) {
                return $result['data'];
            }

            // Return fallback if API fails
            return $this->generateRealisticFallbackData($pairData);
        });
    }

    /**
     * Get a real-time quote for a forex pair (shorter cache)
     */
    public function getForexQuote(string $symbol): ?array
    {
        $cacheKey = "forex_quote_$symbol";
        $ttl = self::CACHE_TTL['FOREX_QUOTE'];

        return Cache::remember($cacheKey, $ttl, function () use ($symbol) {
            return $this->fetchSingleForexPair($symbol);
        });
    }

    /**
     * Fetch historical chart data for a forex pair
     */
    public function fetchForexChartData(string $symbol, string $timeframe = '1D', int $limit = 100): array
    {
        $cacheKey = "forex_chart_{$symbol}_{$timeframe}_$limit";
        $ttl = self::CACHE_TTL['CHART_DATA'];

        return Cache::remember($cacheKey, $ttl, function () use ($symbol, $timeframe, $limit) {
            $allPairs = $this->getAllPairs();
            $pairData = collect($allPairs)->firstWhere('symbol', $symbol);

            if (!$pairData) {
                return ['success' => false, 'error' => 'Pair not found'];
            }

            $apiKey = config('services.polygon.key');
            if (!$apiKey) {
                return ['success' => false, 'error' => 'API key not configured'];
            }

            try {
                $to = now()->format('Y-m-d');
                $from = now()->subDays(30)->format('Y-m-d');

                $url = "https://api.polygon.io/v2/aggs/ticker/{$pairData['polygon']}/range/1/$timeframe/$from/$to";

                $response = Http::timeout(15)->get($url, [
                    'adjusted' => 'true',
                    'sort' => 'asc',
                    'limit' => $limit,
                    'apiKey' => $apiKey
                ]);

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

                if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                    return ['success' => false, 'error' => 'No data available'];
                }

                // Transform to format expected by chart
                $chartData = collect($data['results'])->map(function ($candle) {
                    return [
                        'time' => $candle['t'] / 1000, // Convert to seconds
                        'open' => $candle['o'],
                        'high' => $candle['h'],
                        'low' => $candle['l'],
                        'close' => $candle['c'],
                        'volume' => $candle['v'] ?? 0
                    ];
                })->toArray();

                return [
                    'success' => true,
                    'data' => $chartData,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe
                ];

            } catch (Throwable $e) {
                Log::error("Failed to fetch forex chart data", [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Fetch Forex pairs with intelligent rate limit handling
     * DEPRECATED: Use fetchSingleForexPair() for on-demand loading
     */
    public function fetchForexPairs(): array
    {
        $cacheKey = 'forex_pairs_complete_data';
        $ttl = self::CACHE_TTL['FOREX_PAIRS'];

        return Cache::remember($cacheKey, $ttl, function () use ($ttl) {
            $allPairs = $this->getAllPairs();
            $existingData = Cache::get('forex_pairs_stored_data', []);

            // Use time-based batching instead of rotation
            $pairsBatch = $this->getTimeBasedBatch($allPairs);

            // Fetch live data with smart rate limiting
            $liveBatchData = $this->fetchPairsBatchFromPolygonOptimized($pairsBatch);

            // Merge with existing data
            $updatedData = $this->mergeForexData($existingData, $liveBatchData, $allPairs);

            // Store complete dataset
            Cache::put('forex_pairs_stored_data', $updatedData, $ttl + 3600);

            return $updatedData;
        });
    }

    /**
     * Time-based batching to avoid hitting same pairs repeatedly
     */
    private function getTimeBasedBatch(array $allPairs): array
    {
        $majorPairs = $this->getMajorPairs();
        $otherPairs = $this->getOtherPairs();
        $batchSize = 5;

        // Get current time-based index
        $currentHour = (int)date('H');
        $currentMinute = (int)date('i');
        $timeSlot = floor(($currentHour * 60 + $currentMinute) / 2);
        $rotationIndex = $timeSlot % (ceil(count($allPairs) / $batchSize));

        $majorCount = count($majorPairs);
        $majorBatches = ceil($majorCount / $batchSize);

        if ($rotationIndex < $majorBatches) {
            $startIndex = $rotationIndex * $batchSize;
            return array_slice($majorPairs, $startIndex, $batchSize);
        }

        $otherStartIndex = ($rotationIndex - $majorBatches) * $batchSize;
        return array_slice($otherPairs, $otherStartIndex, $batchSize);
    }

    /**
     * Optimized batch fetching with adaptive rate limiting
     */
    private function fetchPairsBatchFromPolygonOptimized(array $pairsBatch): array
    {
        $apiKey = config('services.polygon.key');
        if (!$apiKey) {
            Log::warning('Polygon API key not configured');
            return [];
        }

        $forexData = [];
        $successfulRequests = 0;
        $rateLimitInfo = $this->getRateLimitState();
        $maxRequests = $this->calculateOptimalRequestCount($rateLimitInfo);

        foreach ($pairsBatch as $pairData) {
            if ($successfulRequests >= $maxRequests) {
                Log::info('Reached optimal request limit', [
                    'successful' => $successfulRequests,
                    'max_allowed' => $maxRequests
                ]);
                break;
            }

            try {
                $result = $this->fetchSinglePairWithBackoff($pairData, $apiKey);

                if ($result['success']) {
                    $forexData[] = $result['data'];
                    $successfulRequests++;
                    $this->updateRateLimitState();

                    // Adaptive delay
                    $delay = $this->calculateAdaptiveDelay($rateLimitInfo, $successfulRequests);
                    usleep($delay * 1000);

                } elseif ($result['rate_limited']) {
                    Log::warning('Rate limit detected, stopping batch', [
                        'pair' => $pairData['symbol'],
                        'requests_completed' => $successfulRequests
                    ]);
                    $this->recordRateLimitHit();
                    break;
                }

            } catch (Throwable $e) {
                Log::warning("Error fetching {$pairData['symbol']}", ['error' => $e->getMessage()]);
                continue;
            }
        }

        return $forexData;
    }

    /**
     * Fetch a single pair with exponential backoff
     */
    private function fetchSinglePairWithBackoff(array $pairData, string $apiKey): array
    {
        $maxAttempts = 3;
        $baseDelay = 500;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $url = "https://api.polygon.io/v2/aggs/ticker/{$pairData['polygon']}/prev";

                $response = Http::timeout(10)->get($url, [
                    'adjusted' => 'true',
                    'apiKey' => $apiKey
                ]);

                if ($response->status() === 429) {
                    return [
                        'success' => false,
                        'rate_limited' => true,
                        'retry_after' => $response->header('Retry-After') ?? 60
                    ];
                }

                if (!$response->successful()) {
                    if ($attempt < $maxAttempts) {
                        $delay = $baseDelay * pow(2, $attempt - 1);
                        usleep($delay * 1000);
                        continue;
                    }
                    return ['success' => false, 'rate_limited' => false];
                }

                $data = $response->json();
                if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                    return ['success' => false, 'rate_limited' => false];
                }

                $result = $data['results'][0];
                $formattedData = $this->formatForexData($pairData, $result);

                return ['success' => true, 'data' => $formattedData];

            } catch (Throwable $e) {
                Log::warning("Attempt $attempt failed for {$pairData['symbol']}", [
                    'error' => $e->getMessage()
                ]);
                if ($attempt < $maxAttempts) {
                    usleep($baseDelay * pow(2, $attempt - 1) * 1000);
                }
            }
        }

        return ['success' => false, 'rate_limited' => false];
    }

    /**
     * Format single forex data point
     */
    private function formatForexData(array $pairData, array $result): array
    {
        $open = $result['o'] ?? 0;
        $close = $result['c'] ?? 0;
        $change = $open > 0 ? (($close - $open) / $open) * 100 : 0;

        return [
            'symbol' => $pairData['symbol'],
            'name' => $pairData['name'],
            'price' => (string)round($close, 5),
            'change' => (string)round($change, 2),
            'high' => (string)round($result['h'] ?? 0, 5),
            'low' => (string)round($result['l'] ?? 0, 5),
            'volume' => (string)round($result['v'] ?? 0),
            'updated_at' => now()->toISOString(),
            'source' => 'polygon',
            'stale' => false,
        ];
    }

    /**
     * Rate limit state tracking
     */
    private function getRateLimitState(): array
    {
        $cached = Cache::get('polygon_rate_limit_state');
        if (is_array($cached)) {
            return $cached;
        }

        return [
            'requests_made' => 0,
            'requests_limit' => 600,
            'last_reset' => now()->timestamp,
            'hits_recorded' => 0,
        ];
    }

    /**
     * Update rate limit state
     */
    private function updateRateLimitState(): void
    {
        $state = $this->getRateLimitState();
        $state['requests_made'] = $state['requests_made'] + 1;
        Cache::put('polygon_rate_limit_state', $state, 3600);
    }

    /**
     * Record a rate limit hit
     */
    private function recordRateLimitHit(): void
    {
        $state = $this->getRateLimitState();
        $state['hits_recorded']++;

        if ($state['hits_recorded'] > 3) {
            Log::alert('Frequent rate limit hits detected', ['hits' => $state['hits_recorded']]);
        }

        Cache::put('polygon_rate_limit_state', $state, 3600);
    }

    /**
     * Calculate optimal request count based on remaining quota
     */
    private function calculateOptimalRequestCount(array $rateLimitInfo): int
    {
        $totalLimit = $rateLimitInfo['requests_limit'];
        $alreadyMade = $rateLimitInfo['requests_made'];
        $remaining = max(0, $totalLimit - $alreadyMade);

        $safeToUse = (int)($remaining * 0.8);

        return min($safeToUse, 5);
    }

    /**
     * Calculate adaptive delay between requests
     */
    private function calculateAdaptiveDelay(array $rateLimitInfo, int $requestsThisBatch): int
    {
        $percentageUsed = ($rateLimitInfo['requests_made'] / $rateLimitInfo['requests_limit']) * 100;

        if ($percentageUsed > 90) {
            return 2000;
        } elseif ($percentageUsed > 70) {
            return 1000;
        } elseif ($percentageUsed > 50) {
            return 500;
        }

        return 200;
    }

    /**
     * Get major forex pairs
     */
    private function getMajorPairs(): array
    {
        return [
            ['symbol' => 'EUR/USD', 'polygon' => 'C:EURUSD', 'name' => 'Euro vs US Dollar', 'priority' => 1],
            ['symbol' => 'GBP/USD', 'polygon' => 'C:GBPUSD', 'name' => 'British Pound vs US Dollar', 'priority' => 1],
            ['symbol' => 'USD/JPY', 'polygon' => 'C:USDJPY', 'name' => 'US Dollar vs Japanese Yen', 'priority' => 1],
            ['symbol' => 'USD/CHF', 'polygon' => 'C:USDCHF', 'name' => 'US Dollar vs Swiss Franc', 'priority' => 1],
            ['symbol' => 'AUD/USD', 'polygon' => 'C:AUDUSD', 'name' => 'Australian Dollar vs US Dollar', 'priority' => 1],
            ['symbol' => 'USD/CAD', 'polygon' => 'C:USDCAD', 'name' => 'US Dollar vs Canadian Dollar', 'priority' => 1],
            ['symbol' => 'NZD/USD', 'polygon' => 'C:NZDUSD', 'name' => 'New Zealand Dollar vs US Dollar', 'priority' => 1],
            ['symbol' => 'EUR/GBP', 'polygon' => 'C:EURGBP', 'name' => 'Euro vs British Pound', 'priority' => 1],
            ['symbol' => 'EUR/JPY', 'polygon' => 'C:EURJPY', 'name' => 'Euro vs Japanese Yen', 'priority' => 1],
            ['symbol' => 'GBP/JPY', 'polygon' => 'C:GBPJPY', 'name' => 'British Pound vs Japanese Yen', 'priority' => 1],
        ];
    }

    /**
     * Get other forex pairs
     */
    private function getOtherPairs(): array
    {
        return [
            ['symbol' => 'EUR/CHF', 'polygon' => 'C:EURCHF', 'name' => 'Euro vs Swiss Franc', 'priority' => 2],
            ['symbol' => 'GBP/CHF', 'polygon' => 'C:GBPCHF', 'name' => 'British Pound vs Swiss Franc', 'priority' => 2],
            ['symbol' => 'AUD/JPY', 'polygon' => 'C:AUDJPY', 'name' => 'Australian Dollar vs Japanese Yen', 'priority' => 2],
            ['symbol' => 'CAD/JPY', 'polygon' => 'C:CADJPY', 'name' => 'Canadian Dollar vs Japanese Yen', 'priority' => 2],
            ['symbol' => 'CHF/JPY', 'polygon' => 'C:CHFJPY', 'name' => 'Swiss Franc vs Japanese Yen', 'priority' => 2],
            ['symbol' => 'NZD/JPY', 'polygon' => 'C:NZDJPY', 'name' => 'New Zealand Dollar vs Japanese Yen', 'priority' => 2],
            ['symbol' => 'EUR/AUD', 'polygon' => 'C:EURAUD', 'name' => 'Euro vs Australian Dollar', 'priority' => 2],
            ['symbol' => 'EUR/CAD', 'polygon' => 'C:EURCAD', 'name' => 'Euro vs Canadian Dollar', 'priority' => 2],
            ['symbol' => 'EUR/NZD', 'polygon' => 'C:EURNZD', 'name' => 'Euro vs New Zealand Dollar', 'priority' => 2],
            ['symbol' => 'GBP/AUD', 'polygon' => 'C:GBPAUD', 'name' => 'British Pound vs Australian Dollar', 'priority' => 2],
            ['symbol' => 'GBP/CAD', 'polygon' => 'C:GBPCAD', 'name' => 'British Pound vs Canadian Dollar', 'priority' => 2],
            ['symbol' => 'AUD/CAD', 'polygon' => 'C:AUDCAD', 'name' => 'Australian Dollar vs Canadian Dollar', 'priority' => 2],
            ['symbol' => 'AUD/NZD', 'polygon' => 'C:AUDNZD', 'name' => 'Australian Dollar vs New Zealand Dollar', 'priority' => 2],
            ['symbol' => 'CAD/CHF', 'polygon' => 'C:CADCHF', 'name' => 'Canadian Dollar vs Swiss Franc', 'priority' => 2],
            ['symbol' => 'NZD/CAD', 'polygon' => 'C:NZDCAD', 'name' => 'New Zealand Dollar vs Canadian Dollar', 'priority' => 2],
            ['symbol' => 'USD/SGD', 'polygon' => 'C:USDSGD', 'name' => 'US Dollar vs Singapore Dollar', 'priority' => 2],
            ['symbol' => 'USD/HKD', 'polygon' => 'C:USDHKD', 'name' => 'US Dollar vs Hong Kong Dollar', 'priority' => 2],
            ['symbol' => 'USD/DKK', 'polygon' => 'C:USDDKK', 'name' => 'US Dollar vs Danish Krone', 'priority' => 2],
            ['symbol' => 'USD/NOK', 'polygon' => 'C:USDNOK', 'name' => 'US Dollar vs Norwegian Krone', 'priority' => 2],
            ['symbol' => 'USD/SEK', 'polygon' => 'C:USDSEK', 'name' => 'US Dollar vs Swedish Krona', 'priority' => 2],
            ['symbol' => 'USD/ZAR', 'polygon' => 'C:USDZAR', 'name' => 'US Dollar vs South African Rand', 'priority' => 2],
            ['symbol' => 'USD/TRY', 'polygon' => 'C:USDTRY', 'name' => 'US Dollar vs Turkish Lira', 'priority' => 2],
            ['symbol' => 'USD/MXN', 'polygon' => 'C:USDMXN', 'name' => 'US Dollar vs Mexican Peso', 'priority' => 2],
            ['symbol' => 'USD/PLN', 'polygon' => 'C:USDPLN', 'name' => 'US Dollar vs Polish Zloty', 'priority' => 2],
            ['symbol' => 'USD/HUF', 'polygon' => 'C:USDHUF', 'name' => 'US Dollar vs Hungarian Forint', 'priority' => 2],
            ['symbol' => 'USD/CZK', 'polygon' => 'C:USDCZK', 'name' => 'US Dollar vs Czech Koruna', 'priority' => 2],
            ['symbol' => 'USD/RUB', 'polygon' => 'C:USDRUB', 'name' => 'US Dollar vs Russian Ruble', 'priority' => 2],
            ['symbol' => 'USD/CNH', 'polygon' => 'C:USDCNH', 'name' => 'US Dollar vs Chinese Yuan', 'priority' => 2],
            ['symbol' => 'USD/INR', 'polygon' => 'C:USDINR', 'name' => 'US Dollar vs Indian Rupee', 'priority' => 2],
            ['symbol' => 'USD/BRL', 'polygon' => 'C:USDBRL', 'name' => 'US Dollar vs Brazilian Real', 'priority' => 2],
            ['symbol' => 'USD/KRW', 'polygon' => 'C:USDKRW', 'name' => 'US Dollar vs South Korean Won', 'priority' => 2],
            ['symbol' => 'USD/THB', 'polygon' => 'C:USDTHB', 'name' => 'US Dollar vs Thai Baht', 'priority' => 2],
            ['symbol' => 'USD/MYR', 'polygon' => 'C:USDMYR', 'name' => 'US Dollar vs Malaysian Ringgit', 'priority' => 2],
            ['symbol' => 'USD/IDR', 'polygon' => 'C:USDIDR', 'name' => 'US Dollar vs Indonesian Rupiah', 'priority' => 2],
            ['symbol' => 'USD/ARS', 'polygon' => 'C:USDARS', 'name' => 'US Dollar vs Argentine Peso', 'priority' => 2],
            ['symbol' => 'USD/CLP', 'polygon' => 'C:USDCLP', 'name' => 'US Dollar vs Chilean Peso', 'priority' => 2],
            ['symbol' => 'USD/COP', 'polygon' => 'C:USDCOP', 'name' => 'US Dollar vs Colombian Peso', 'priority' => 2],
            ['symbol' => 'USD/PEN', 'polygon' => 'C:USDPEN', 'name' => 'US Dollar vs Peruvian Sol', 'priority' => 2],
        ];
    }

    /**
     * Get all available forex pairs
     */
    private function getAllPairs(): array
    {
        return array_merge($this->getMajorPairs(), $this->getOtherPairs());
    }

    /**
     * Generate realistic fallback data for forex pairs
     */
    private function generateRealisticFallbackData(array $pairData): array
    {
        $realisticPrices = [
            'EUR/USD' => ['price' => 1.08500, 'volatility' => 0.00500],
            'GBP/USD' => ['price' => 1.27500, 'volatility' => 0.00600],
            'USD/JPY' => ['price' => 150.200, 'volatility' => 0.500],
            'USD/CHF' => ['price' => 0.88000, 'volatility' => 0.00300],
            'AUD/USD' => ['price' => 0.67500, 'volatility' => 0.00400],
            'USD/CAD' => ['price' => 1.36500, 'volatility' => 0.00500],
            'NZD/USD' => ['price' => 0.61500, 'volatility' => 0.00400],
            'EUR/GBP' => ['price' => 0.85000, 'volatility' => 0.00300],
            'EUR/JPY' => ['price' => 163.000, 'volatility' => 0.600],
            'GBP/JPY' => ['price' => 192.000, 'volatility' => 0.700],
            'EUR/CHF' => ['price' => 0.97000, 'volatility' => 0.00200],
            'GBP/CHF' => ['price' => 1.12000, 'volatility' => 0.00300],
            'AUD/JPY' => ['price' => 95.500, 'volatility' => 0.500],
            'CAD/JPY' => ['price' => 108.200, 'volatility' => 0.400],
            'CHF/JPY' => ['price' => 165.800, 'volatility' => 0.600],
            'NZD/JPY' => ['price' => 88.300, 'volatility' => 0.450],
            'EUR/AUD' => ['price' => 1.63000, 'volatility' => 0.00800],
            'EUR/CAD' => ['price' => 1.46000, 'volatility' => 0.00600],
            'EUR/NZD' => ['price' => 1.77000, 'volatility' => 0.00900],
            'GBP/AUD' => ['price' => 1.92000, 'volatility' => 0.01000],
            'GBP/CAD' => ['price' => 1.71000, 'volatility' => 0.00800],
            'AUD/CAD' => ['price' => 0.89500, 'volatility' => 0.00400],
            'AUD/NZD' => ['price' => 1.08500, 'volatility' => 0.00500],
            'CAD/CHF' => ['price' => 0.66000, 'volatility' => 0.00300],
            'NZD/CAD' => ['price' => 0.82500, 'volatility' => 0.00400],
            'USD/SGD' => ['price' => 1.34500, 'volatility' => 0.00500],
            'USD/HKD' => ['price' => 7.83000, 'volatility' => 0.02000],
            'USD/DKK' => ['price' => 6.92000, 'volatility' => 0.03000],
            'USD/NOK' => ['price' => 10.6500, 'volatility' => 0.10000],
            'USD/SEK' => ['price' => 10.4800, 'volatility' => 0.08000],
            'USD/ZAR' => ['price' => 18.2500, 'volatility' => 0.20000],
            'USD/TRY' => ['price' => 32.1500, 'volatility' => 0.50000],
            'USD/MXN' => ['price' => 17.2800, 'volatility' => 0.15000],
            'USD/PLN' => ['price' => 4.02000, 'volatility' => 0.03000],
            'USD/HUF' => ['price' => 355.00, 'volatility' => 2.00000],
            'USD/CZK' => ['price' => 22.8000, 'volatility' => 0.15000],
            'USD/RUB' => ['price' => 92.5000, 'volatility' => 1.00000],
            'USD/CNH' => ['price' => 7.25000, 'volatility' => 0.02000],
            'USD/INR' => ['price' => 83.1500, 'volatility' => 0.20000],
            'USD/BRL' => ['price' => 4.95000, 'volatility' => 0.05000],
            'USD/KRW' => ['price' => 1320.00, 'volatility' => 5.00000],
            'USD/THB' => ['price' => 35.8000, 'volatility' => 0.15000],
            'USD/MYR' => ['price' => 4.72000, 'volatility' => 0.03000],
            'USD/IDR' => ['price' => 15600.0, 'volatility' => 50.0000],
            'USD/ARS' => ['price' => 350.000, 'volatility' => 5.00000],
            'USD/CLP' => ['price' => 920.000, 'volatility' => 10.0000],
            'USD/COP' => ['price' => 3900.00, 'volatility' => 50.0000],
            'USD/PEN' => ['price' => 3.78000, 'volatility' => 0.03000],
        ];

        $symbol = $pairData['symbol'];
        $baseData = $realisticPrices[$symbol] ?? ['price' => 1.00000, 'volatility' => 0.01000];

        $variation = (mt_rand(-100, 100) / 10000) * $baseData['volatility'];
        $currentPrice = $baseData['price'] + $variation;

        $changePercent = round((mt_rand(-200, 200) / 10000), 2);

        $dailyRange = $baseData['volatility'] * $currentPrice;
        $high = $currentPrice + ($dailyRange * 0.6);
        $low = $currentPrice - ($dailyRange * 0.4);

        $volume = mt_rand(1000, 50000);

        return [
            'symbol' => $pairData['symbol'],
            'name' => $pairData['name'],
            'price' => (string)round($currentPrice, 5),
            'change' => (string)$changePercent,
            'high' => (string)round($high, 5),
            'low' => (string)round($low, 5),
            'volume' => (string)$volume,
            'stale' => true,
            'updated_at' => now()->subHours(mt_rand(1, 12))->toISOString(),
            'source' => 'fallback',
        ];
    }

    /**
     * Merge forex data with fallback generation
     */
    private function mergeForexData(array $existingData, array $newData, array $allPairs): array
    {
        $existingMap = [];
        foreach ($existingData as $item) {
            $existingMap[$item['symbol']] = $item;
        }

        foreach ($newData as $item) {
            $existingMap[$item['symbol']] = $item;
        }

        $result = [];
        foreach ($allPairs as $pair) {
            $symbol = $pair['symbol'];
            if (isset($existingMap[$symbol]) && !($existingMap[$symbol]['stale'] ?? false)) {
                $result[] = $existingMap[$symbol];
            } elseif (isset($existingMap[$symbol]) && ($existingMap[$symbol]['stale'] ?? false)) {
                $result[] = $existingMap[$symbol];
            } else {
                $result[] = $this->generateRealisticFallbackData($pair);
            }
        }

        return $result;
    }

    /**
     * Enhanced progress tracking with rate limit awareness
     */
    public function getForexUpdateProgress(): array
    {
        $majorPairs = $this->getMajorPairs();
        $otherPairs = $this->getOtherPairs();
        $rateLimitState = $this->getRateLimitState();
        $batchSize = 5;

        $totalPairs = count($majorPairs) + count($otherPairs);
        $totalBatches = ceil($totalPairs / $batchSize);

        $currentHour = (int)date('H');
        $currentMinute = (int)date('i');
        $timeSlot = floor(($currentHour * 60 + $currentMinute) / 2);
        $currentBatch = $timeSlot % $totalBatches;

        $remainingQuota = max(0, $rateLimitState['requests_limit'] - $rateLimitState['requests_made']);
        $quotaPercentage = ($rateLimitState['requests_made'] / $rateLimitState['requests_limit']) * 100;

        return [
            'total_pairs' => $totalPairs,
            'major_pairs' => count($majorPairs),
            'other_pairs' => count($otherPairs),
            'batch_size' => $batchSize,
            'total_batches' => $totalBatches,
            'current_batch' => $currentBatch + 1,
            'progress_percentage' => round(($currentBatch / $totalBatches) * 100, 1),
            'phase' => $currentBatch < ceil(count($majorPairs) / $batchSize) ? 'updating_majors' : 'updating_others',
            'next_update_in' => '2 minutes',
            'rate_limit_quota_remaining' => $remainingQuota,
            'rate_limit_usage' => round($quotaPercentage, 1) . '%',
            'rate_limit_hits_this_hour' => $rateLimitState['hits_recorded'],
        ];
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

        $price = $this->fetchPriceDataWithProviderFallback('ethereum');

        if ($price > 0.0) {
            Cache::put($cacheKey, $price, self::CACHE_TTL['PRICE_DATA']);
        } else {
            Log::warning('Using fallback ETH price');
            $price = 2000.0;
        }

        return $price;
    }

    public function fetchPriceData(string $symbol): float
    {
        $cacheKey = "price_data_$symbol";

        $cached = Cache::get($cacheKey);
        if (is_numeric($cached) && $cached > 0) {
            return (float)$cached;
        }

        $price = $this->fetchPriceDataWithProviderFallback($symbol);

        if ($price > 0.0) {
            Cache::put($cacheKey, $price, self::CACHE_TTL['PRICE_DATA']);
        } else {
            Log::warning("Failed to fetch price for symbol: $symbol. Returning 0.0");
        }

        return $price;
    }

    private function fetchPriceDataWithProviderFallback(string $symbol): float
    {
        foreach (self::API_PROVIDERS['price'] as $provider) {
            $price = $this->fetchPriceDataWithRetry($provider, $symbol);

            if ($price > 0.0) {
                return $price;
            }

            Log::warning("Failed to fetch price data from $provider after retries, trying next provider", [
                'symbol' => $symbol,
            ]);
        }

        return 0.0;
    }

    private function fetchPriceDataWithRetry(string $provider, string $symbol): float
    {
        $maxRetries = self::MAX_RETRIES;
        $rateLimitRetries = 0;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $result = $this->fetchPriceDataFromProvider($provider, $symbol);
            $price = $result['price'] ?? 0.0;

            if ($price > 0.0) {
                return $price;
            }

            if (($result['status'] ?? 0) === 429) {
                $rateLimitRetries++;

                if ($rateLimitRetries > self::RATE_LIMIT_MAX_RETRIES) {
                    Log::warning("Rate limit retry limit exceeded for $provider price", [
                        'symbol' => $symbol,
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

    private function fetchPriceDataFromProvider(string $provider, string $symbol): array
    {
        try {
            return match ($provider) {
                'cryptocompare' => $this->fetchPriceDataFromCryptoCompare($symbol),
                'coinmarketcap' => $this->fetchPriceDataFromCoinMarketCap($symbol),
                'coingecko' => $this->fetchPriceDataFromCoinGeckoSimple($symbol),
                default => ['price' => 0.0, 'status' => 501],
            };
        } catch (Throwable $e) {
            Log::error("Exception fetching price data from $provider", [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            return ['price' => 0.0, 'status' => 500];
        }
    }

    /**
     * @throws ConnectionException
     */
    private function fetchPriceDataFromCryptoCompare(string $symbol): array
    {
        $mappedSymbol = $this->getMappedSymbol($symbol, 'cryptocompare');
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
    private function fetchPriceDataFromCoinMarketCap(string $symbol): array
    {
        $mappedSymbol = strtoupper($this->getMappedSymbol($symbol, 'coinmarketcap'));
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
    private function fetchPriceDataFromCoinGeckoSimple(string $symbol): array
    {
        $mappedSymbol = $this->getMappedSymbol($symbol, 'coingecko');
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
