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
        'FOREX_PAIRS' => 120,     // 2 minutes
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
     * Fetch Forex pairs with smart batching to respect Polygon rate limits
     */
    public function fetchForexPairs(): array
    {
        $cacheKey = 'forex_pairs_complete_data';
        $ttl = self::CACHE_TTL['FOREX_PAIRS'];

        return Cache::remember($cacheKey, $ttl, function () use ($ttl) {
            $allPairs = $this->getAllPairs();
            $existingData = Cache::get('forex_pairs_stored_data', []);

            // Use rotation to determine which batch to update this cycle
            $rotationIndex = Cache::get('forex_rotation_index', 0);
            $batchSize = 5; // Polygon allows 5 requests per minute

            // Calculate which pairs to update in this cycle
            $totalBatches = ceil(count($allPairs) / $batchSize);
            $currentBatchIndex = $rotationIndex % $totalBatches;

            // Get the pairs for this batch
            $startIndex = $currentBatchIndex * $batchSize;
            $pairsBatch = array_slice($allPairs, $startIndex, $batchSize);

            // Fetch live data for this batch
            $liveBatchData = $this->fetchPairsBatchFromPolygon($pairsBatch);

            // Merge with existing data
            $updatedData = $this->mergeForexData($existingData, $liveBatchData, $allPairs);

            // Store the complete dataset for next time (longer TTL than cache)
            Cache::put('forex_pairs_stored_data', $updatedData, $ttl + 3600);

            // Update rotation for next cycle
            $nextIndex = ($rotationIndex + 1);
            Cache::put('forex_rotation_index', $nextIndex, 86400);

            Log::info("Forex data rotation updated", [
                'batch' => $currentBatchIndex + 1 . '/' . $totalBatches,
                'pairs_updated' => count($liveBatchData),
                'total_pairs' => count($updatedData),
                'next_batch' => ($nextIndex % $totalBatches) + 1
            ]);

            return $updatedData;
        });
    }

    /**
     * Get all available forex pairs
     */
    private function getAllPairs(): array
    {
        return [
            ['symbol' => 'EUR/USD', 'polygon' => 'C:EURUSD', 'name' => 'Euro vs US Dollar'],
            ['symbol' => 'GBP/USD', 'polygon' => 'C:GBPUSD', 'name' => 'British Pound vs US Dollar'],
            ['symbol' => 'USD/JPY', 'polygon' => 'C:USDJPY', 'name' => 'US Dollar vs Japanese Yen'],
            ['symbol' => 'USD/CHF', 'polygon' => 'C:USDCHF', 'name' => 'US Dollar vs Swiss Franc'],
            ['symbol' => 'AUD/USD', 'polygon' => 'C:AUDUSD', 'name' => 'Australian Dollar vs US Dollar'],
            ['symbol' => 'USD/CAD', 'polygon' => 'C:USDCAD', 'name' => 'US Dollar vs Canadian Dollar'],
            ['symbol' => 'NZD/USD', 'polygon' => 'C:NZDUSD', 'name' => 'New Zealand Dollar vs US Dollar'],
            ['symbol' => 'EUR/GBP', 'polygon' => 'C:EURGBP', 'name' => 'Euro vs British Pound'],
            ['symbol' => 'EUR/JPY', 'polygon' => 'C:EURJPY', 'name' => 'Euro vs Japanese Yen'],
            ['symbol' => 'GBP/JPY', 'polygon' => 'C:GBPJPY', 'name' => 'British Pound vs Japanese Yen'],
            ['symbol' => 'EUR/CHF', 'polygon' => 'C:EURCHF', 'name' => 'Euro vs Swiss Franc'],
            ['symbol' => 'GBP/CHF', 'polygon' => 'C:GBPCHF', 'name' => 'British Pound vs Swiss Franc'],
            ['symbol' => 'AUD/JPY', 'polygon' => 'C:AUDJPY', 'name' => 'Australian Dollar vs Japanese Yen'],
            ['symbol' => 'CAD/JPY', 'polygon' => 'C:CADJPY', 'name' => 'Canadian Dollar vs Japanese Yen'],
            ['symbol' => 'CHF/JPY', 'polygon' => 'C:CHFJPY', 'name' => 'Swiss Franc vs Japanese Yen'],
            ['symbol' => 'NZD/JPY', 'polygon' => 'C:NZDJPY', 'name' => 'New Zealand Dollar vs Japanese Yen'],
            ['symbol' => 'EUR/AUD', 'polygon' => 'C:EURAUD', 'name' => 'Euro vs Australian Dollar'],
            ['symbol' => 'EUR/CAD', 'polygon' => 'C:EURCAD', 'name' => 'Euro vs Canadian Dollar'],
            ['symbol' => 'EUR/NZD', 'polygon' => 'C:EURNZD', 'name' => 'Euro vs New Zealand Dollar'],
            ['symbol' => 'GBP/AUD', 'polygon' => 'C:GBPAUD', 'name' => 'British Pound vs Australian Dollar'],
            ['symbol' => 'GBP/CAD', 'polygon' => 'C:GBPCAD', 'name' => 'British Pound vs Canadian Dollar'],
            ['symbol' => 'AUD/CAD', 'polygon' => 'C:AUDCAD', 'name' => 'Australian Dollar vs Canadian Dollar'],
            ['symbol' => 'AUD/NZD', 'polygon' => 'C:AUDNZD', 'name' => 'Australian Dollar vs New Zealand Dollar'],
            ['symbol' => 'CAD/CHF', 'polygon' => 'C:CADCHF', 'name' => 'Canadian Dollar vs Swiss Franc'],
            ['symbol' => 'NZD/CAD', 'polygon' => 'C:NZDCAD', 'name' => 'New Zealand Dollar vs Canadian Dollar'],
            ['symbol' => 'USD/SGD', 'polygon' => 'C:USDSGD', 'name' => 'US Dollar vs Singapore Dollar'],
            ['symbol' => 'USD/HKD', 'polygon' => 'C:USDHKD', 'name' => 'US Dollar vs Hong Kong Dollar'],
            ['symbol' => 'USD/DKK', 'polygon' => 'C:USDDKK', 'name' => 'US Dollar vs Danish Krone'],
            ['symbol' => 'USD/NOK', 'polygon' => 'C:USDNOK', 'name' => 'US Dollar vs Norwegian Krone'],
            ['symbol' => 'USD/SEK', 'polygon' => 'C:USDSEK', 'name' => 'US Dollar vs Swedish Krona'],
            ['symbol' => 'USD/ZAR', 'polygon' => 'C:USDZAR', 'name' => 'US Dollar vs South African Rand'],
            ['symbol' => 'USD/TRY', 'polygon' => 'C:USDTRY', 'name' => 'US Dollar vs Turkish Lira'],
            ['symbol' => 'USD/MXN', 'polygon' => 'C:USDMXN', 'name' => 'US Dollar vs Mexican Peso'],
            ['symbol' => 'USD/PLN', 'polygon' => 'C:USDPLN', 'name' => 'US Dollar vs Polish Zloty'],
            ['symbol' => 'USD/HUF', 'polygon' => 'C:USDHUF', 'name' => 'US Dollar vs Hungarian Forint'],
            ['symbol' => 'USD/CZK', 'polygon' => 'C:USDCZK', 'name' => 'US Dollar vs Czech Koruna'],
            ['symbol' => 'USD/RUB', 'polygon' => 'C:USDRUB', 'name' => 'US Dollar vs Russian Ruble'],
            ['symbol' => 'USD/CNH', 'polygon' => 'C:USDCNH', 'name' => 'US Dollar vs Chinese Yuan'],
            ['symbol' => 'USD/INR', 'polygon' => 'C:USDINR', 'name' => 'US Dollar vs Indian Rupee'],
            ['symbol' => 'USD/BRL', 'polygon' => 'C:USDBRL', 'name' => 'US Dollar vs Brazilian Real'],
            ['symbol' => 'USD/KRW', 'polygon' => 'C:USDKRW', 'name' => 'US Dollar vs South Korean Won'],
            ['symbol' => 'USD/THB', 'polygon' => 'C:USDTHB', 'name' => 'US Dollar vs Thai Baht'],
            ['symbol' => 'USD/MYR', 'polygon' => 'C:USDMYR', 'name' => 'US Dollar vs Malaysian Ringgit'],
            ['symbol' => 'USD/IDR', 'polygon' => 'C:USDIDR', 'name' => 'US Dollar vs Indonesian Rupiah'],
            ['symbol' => 'USD/ARS', 'polygon' => 'C:USDARS', 'name' => 'US Dollar vs Argentine Peso'],
            ['symbol' => 'USD/CLP', 'polygon' => 'C:USDCLP', 'name' => 'US Dollar vs Chilean Peso'],
            ['symbol' => 'USD/COP', 'polygon' => 'C:USDCOP', 'name' => 'US Dollar vs Colombian Peso'],
            ['symbol' => 'USD/PEN', 'polygon' => 'C:USDPEN', 'name' => 'US Dollar vs Peruvian Sol'],
        ];
    }

    /**
     * Merge existing forex data with new batch data
     */
    private function mergeForexData(array $existingData, array $newData, array $allPairs): array
    {
        // Create a map of existing data by symbol for easy merging
        $existingMap = [];
        foreach ($existingData as $item) {
            $existingMap[$item['symbol']] = $item;
        }

        // Update with new data
        foreach ($newData as $item) {
            $existingMap[$item['symbol']] = $item;
        }

        // Ensure we have all pairs, even if some are missing data
        $result = [];
        foreach ($allPairs as $pair) {
            $symbol = $pair['symbol'];
            if (isset($existingMap[$symbol])) {
                $result[] = $existingMap[$symbol];
            } else {
                // Fallback data for pairs not yet fetched
                $result[] = [
                    'symbol' => $pair['symbol'],
                    'name' => $pair['name'],
                    'price' => '0.00000',
                    'change' => '0.00',
                    'high' => '0.00000',
                    'low' => '0.00000',
                    'volume' => '0',
                    'stale' => true, // Mark as stale data
                    'updated_at' => now()->subDays()->toISOString(),
                ];
            }
        }

        return $result;
    }

    /**
     * Fetch specific batch of pairs from Polygon
     */
    private function fetchPairsBatchFromPolygon(array $pairsBatch): array
    {
        $apiKey = config('services.polygon.key');
        if (!$apiKey) {
            Log::warning('Polygon API key not configured');
            return [];
        }

        $forexData = [];
        $successfulRequests = 0;

        foreach ($pairsBatch as $pairData) {
            // Stop if we're approaching rate limits (5 requests per minute)
            if ($successfulRequests >= 5) {
                Log::warning('Approaching Polygon rate limit, stopping batch');
                break;
            }

            try {
                $url = "https://api.polygon.io/v2/aggs/ticker/{$pairData['polygon']}/prev";

                $response = Http::timeout(10)->get($url, [
                    'adjusted' => 'true',
                    'apiKey' => $apiKey
                ]);

                if ($response->status() === 429) {
                    Log::warning("Rate limited on Polygon.io - stopping batch");
                    break;
                }

                if (!$response->successful()) {
                    Log::warning("Failed to fetch {$pairData['symbol']} from Polygon", [
                        'status' => $response->status()
                    ]);
                    continue;
                }

                $data = $response->json();
                if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
                    Log::warning("No data for {$pairData['symbol']} from Polygon");
                    continue;
                }

                $result = $data['results'][0];
                $open = $result['o'] ?? 0;
                $close = $result['c'] ?? 0;
                $high = $result['h'] ?? 0;
                $low = $result['l'] ?? 0;

                $change = $open > 0 ? (($close - $open) / $open) * 100 : 0;

                $forexData[] = [
                    'symbol' => $pairData['symbol'],
                    'name' => $pairData['name'],
                    'price' => (string) round($close, 5),
                    'change' => (string) round($change, 2),
                    'high' => (string) round($high, 5),
                    'low' => (string) round($low, 5),
                    'volume' => (string) round($result['v'] ?? 0),
                    'updated_at' => now()->toISOString(),
                ];

                $successfulRequests++;

                // Be nice to the API - wait 1 second between requests to stay within limits
                if ($successfulRequests < count($pairsBatch)) {
                    sleep(1);
                }

            } catch (Throwable $e) {
                Log::warning("Error fetching {$pairData['symbol']} from Polygon", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        Log::info("Fetched forex batch from Polygon", [
            'requested' => count($pairsBatch),
            'successful' => count($forexData),
            'failed' => count($pairsBatch) - count($forexData)
        ]);

        return $forexData;
    }

    /**
     * Get current forex update progress (optional helper method)
     */
    public function getForexUpdateProgress(): array
    {
        $allPairs = $this->getAllPairs();
        $rotationIndex = Cache::get('forex_rotation_index', 0);
        $batchSize = 5;
        $totalBatches = ceil(count($allPairs) / $batchSize);
        $currentBatch = $rotationIndex % $totalBatches;
        $updatedPairs = $currentBatch * $batchSize;

        return [
            'total_pairs' => count($allPairs),
            'updated_pairs' => min($updatedPairs, count($allPairs)),
            'batch_size' => $batchSize,
            'total_batches' => $totalBatches,
            'current_batch' => $currentBatch + 1,
            'progress_percentage' => round(($updatedPairs / count($allPairs)) * 100, 1),
            'next_batch' => (($currentBatch + 1) % $totalBatches) + 1,
            'next_update_in' => '2 minutes',
        ];
    }

    /**
     * Gets market prices via CoinGecko API without pagination and caches results.
     *
     * @return array
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
     *
     * @return array
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
     *
     * @param array $rawData
     * @return array
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

            // Get filtered gateways
            $gateways = collect($wallets)
                ->where('status', '1')
                ->sortBy('status')
                ->values()
                ->toArray();

            if (empty($gateways)) {
                return [];
            }

            // Extract coingecko_ids from gateways
            $coinGeckoIds = collect($gateways)
                ->pluck('coingecko_id')
                ->filter()
                ->unique()
                ->all();

            if (empty($coinGeckoIds)) {
                return $gateways;
            }

            // Get cryptos data
            $cryptos = $this->getCryptos();

            // Create a map of coingecko_id => crypto data for a quick lookup
            $cryptoMap = collect($cryptos)
                ->keyBy('id')
                ->all();

            // Merge gateways with crypto images
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
            return (float) $cached;
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
            return (float) $cached;
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

            return ['price' => (float) $price, 'status' => 200];
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

            return ['price' => (float) $price, 'status' => 200];
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

            return ['price' => (float) $price, 'status' => 200];
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

        // Only cache raw data if it's not the specific coins endpoint,
        // as that is transformed before being cached externally.
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
