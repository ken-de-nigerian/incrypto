<?php

namespace App\Services;

use Exception;
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
        'PRICE_DATA' => 300,       // 5 minutes for real-time price data
        'GAS_PRICE_DATA' => 120,   // 2 minutes for gas price data
        'COIN_LIST' => 120,        // 2 minutes for currency conversion data
        'WALLET_DATA' => 43200,    // 12 hours for wallet data
    ];

    /**
     * Maximum number of retries for API calls.
     */
    private const MAX_RETRIES = 3;

    /**
     * Base delay between retries in seconds (exponential backoff).
     */
    private const RETRY_BASE_DELAY = 1;

    /**
     * Chain ID mapping for supported chains
     */
    private const CHAIN_IDS = [
        'Ethereum' => 1,
        'BSC' => 56,
        'Polygon' => 137,
        'Arbitrum' => 42161,
        'Optimism' => 10,
    ];

    /**
     * Retrieve gateway currencies from the configuration.
     *
     * @return array An array containing gateway currencies.
     */
    public function getGateways(): array
    {
        try {
            // Get wallet addresses from config
            $wallets = config('gateways.wallet_addresses');

            // Ensure it's an array
            if (!is_array($wallets)) {
                return [];
            }

            // Use Laravel Collection to filter and sort
            return collect($wallets)
                ->filter(fn($wallet) => $wallet['status'] == 1)
                ->sortBy('status')
                ->values()
                ->toArray();
        } catch (Throwable $e) {
            Log::error('Error in getGateways(): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch Ethereum gas prices from Etherscan V2 with USD conversion.
     *
     * @param string $chain The blockchain network (e.g., Ethereum, BSC)
     * @return array
     */
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
                // Fetch current ETH price (uses its own caching)
                $ethPrice = $this->fetchEthPrice();

                // Fetch gas oracle data from Etherscan V2 using fetchData helper
                $url = "https://api.etherscan.io/v2/api?module=gastracker&action=gasoracle&chainid=$chainId&apikey=$apiKey";
                $response = $this->fetchData(
                    "etherscan_gas_oracle_$chain",
                    $url,
                    self::CACHE_TTL['GAS_PRICE_DATA'],
                    "Failed to fetch Etherscan V2 Gas Oracle data for chain $chain"
                );

                if ($response['status'] !== '1' || !isset($response['result'])) {
                    throw new Exception('Invalid response from Etherscan V2 Gas Oracle API: ' . ($response['message'] ?? 'Unknown error'));
                }

                $result = $response['result'];

                // Validate required fields (V2 may use camelCase or PascalCase)
                $safeGasPrice = $result['safeGasPrice'] ?? $result['SafeGasPrice'] ?? null;
                $proposeGasPrice = $result['proposeGasPrice'] ?? $result['ProposeGasPrice'] ?? null;
                $fastGasPrice = $result['fastGasPrice'] ?? $result['FastGasPrice'] ?? null;

                if (!$safeGasPrice || !$proposeGasPrice || !$fastGasPrice) {
                    throw new Exception('Missing gas price fields in Etherscan V2 response');
                }

                // Define the calculation logic for USD conversion
                $gweiToUsd = fn($gwei) => ($gwei * 21000 * $ethPrice) / 1e9;

                // Structure and return the data
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
                    'response' => $response ?? null,
                ]);
                return $this->getFallbackGasPrices();
            }
        });
    }

    /**
     * Return fallback gas prices.
     *
     * @return array
     */
    private function getFallbackGasPrices(): array
    {
        return [
            'low' => ['gwei' => 25, 'time' => '~3 min', 'usd' => 3.50],
            'medium' => ['gwei' => 35, 'time' => '~1 min', 'usd' => 4.90],
            'high' => ['gwei' => 50, 'time' => '~15 sec', 'usd' => 7.00],
        ];
    }

    /**
     * Fetch the current price of Ethereum in USD from CoinGecko.
     *
     * @return float
     */
    private function fetchEthPrice(): float
    {
        $cacheKey = 'coingecko_eth_price';
        $url = 'https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd';

        // Use the existing fetchData helper for simple API calls
        $response = $this->fetchData(
            $cacheKey,
            $url,
            self::CACHE_TTL['PRICE_DATA'],
            'Failed to fetch ETH price from CoinGecko'
        );

        // Return the price or a reasonable fallback value
        return $response['ethereum']['usd'] ?? 2000.0;
    }

    /**
     * Fetch CoinGecko coin list with caching
     */
    public function fetchCoinGeckoCoinList(): array
    {
        $cacheKey = 'coinGecko_coin_list';
        $url = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&sparkline=false';

        return $this->fetchData(
            $cacheKey,
            $url,
            self::CACHE_TTL['COIN_LIST'],
            'Failed to fetch CoinGecko coin list'
        );
    }

    /**
     * Fetch CoinGecko market data for specific coin IDs
     */
    private function fetchCoinGeckoMarketData(array $coinIds): array
    {
        $cacheKey = "coinGeckoSpecificCoins_" . implode('_', $coinIds);
        $ids = implode(',', $coinIds);
        $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=$ids&order=market_cap_desc&sparkline=false";

        return $this->fetchData(
            $cacheKey,
            $url,
            self::CACHE_TTL['PRICE_DATA'],
            'Failed to fetch CoinGecko market data'
        );
    }

    /**
     * Fetch crypto data for active gateways using CoinGecko,
     * dynamically resolving CoinGecko IDs by symbol.
     *
     * @return array
     */
    public function fetchGatewaysCrypto(): array
    {
        $gateways = $this->getGateways();

        if (empty($gateways)) {
            return [];
        }

        // Step 1: Get all coins from CoinGecko
        $coinList = $this->fetchCoinGeckoCoinList();

        if (empty($coinList)) {
            Log::warning('Failed to fetch CoinGecko coin list');
            return [];
        }

        // Build a map: symbol (lowercase) => CoinGecko ID
        $symbolToId = collect($coinList)
            ->mapWithKeys(fn($coin) => [strtoupper($coin['symbol']) => $coin['id']])
            ->toArray();

        // Step 2: Map gateway symbols to CoinGecko IDs
        $coinIds = collect($gateways)
            ->pluck('abbreviation')
            ->map(fn($symbol) => $symbolToId[strtoupper($symbol)] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($coinIds)) {
            Log::debug('No matching CoinGecko IDs found for gateways', ['gateways' => $gateways]);
            return [];
        }

        // Step 3: Fetch market data for those CoinGecko IDs
        $coinData = $this->fetchCoinGeckoMarketData($coinIds);

        if (empty($coinData)) {
            return [];
        }

        // Reindex by CoinGecko ID
        $coinDataById = collect($coinData)
            ->keyBy(fn($coin) => strtolower($coin['id']))
            ->toArray();

        // Step 4: Merge with gateways
        $result = [];

        foreach ($gateways as $gateway) {
            $symbol = strtoupper($gateway['abbreviation']);
            $coinId = $symbolToId[$symbol] ?? null;

            if ($coinId && isset($coinDataById[$coinId])) {
                $result[] = array_merge($gateway, ['coin' => $coinDataById[$coinId]]);
            } else {
                Log::debug('No matching CoinGecko data found for gateway', [
                    'gateway' => $gateway,
                    'symbol' => $symbol,
                    'coinId' => $coinId
                ]);
            }
        }

        return $result;
    }

    /**
     * Gets a list of cryptocurrency wallets.
     *
     * @return array
     */
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

    /**
     * Sort wallet data by name
     */
    private function sortWalletData(array $wallets): array
    {
        usort($wallets, fn($a, $b) => strcmp($a['Name'] ?? '', $b['Name'] ?? ''));
        return $wallets;
    }

    /**
     * Gets a single cryptocurrency wallet.
     *
     * @param string $walletId
     * @return array|null
     */
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

    /**
     * Fetches data from API or cache.
     *
     * @param string $cacheKey
     * @param string $apiUrl
     * @param int $ttl
     * @param string $errorContext
     * @return array
     */
    private function fetchData(string $cacheKey, string $apiUrl, int $ttl, string $errorContext = ''): array
    {
        return Cache::remember($cacheKey, $ttl, function () use ($apiUrl, $errorContext) {
            $data = $this->fetchFromAPIWithRetry($apiUrl);

            if (empty($data)) {
                Log::warning("Empty response from API", ['context' => $errorContext, 'url' => $apiUrl]);
                return [];
            }

            return $data;
        });
    }

    /**
     * Fetches data from API with retry logic.
     *
     * @param string $apiUrl
     * @return array
     */
    private function fetchFromAPIWithRetry(string $apiUrl): array
    {
        $retries = 0;

        while ($retries < self::MAX_RETRIES) {
            if ($retries > 0) {
                $delay = self::RETRY_BASE_DELAY * pow(2, $retries - 1);
                sleep($delay);
                Log::debug("Retrying API request after delay", ['url' => $apiUrl, 'delay' => $delay]);
            }

            $response = $this->makeApiRequest($apiUrl);

            if (!empty($response)) {
                return $response;
            }

            $retries++;
            Log::warning("API request attempt failed", ['url' => $apiUrl, 'attempt' => $retries]);
        }

        return [];
    }

    /**
     * Makes generic API request.
     *
     * @param string $apiUrl
     * @return array
     */
    private function makeApiRequest(string $apiUrl): array
    {
        try {
            $apiKey = config('services.coingecko.key', '');
            $urlWithKey = $apiKey && !str_contains($apiUrl, 'etherscan')
                ? $apiUrl . (str_contains($apiUrl, '?') ? '&' : '?') . 'x_cg_api_key=' . $apiKey
                : $apiUrl;

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0'
            ])->timeout(10)->get($urlWithKey);

            if ($response->status() === 429) {
                Log::warning("API rate limit exceeded", ['url' => $apiUrl]);
                return [];
            }

            if ($response->failed()) {
                Log::warning("API request failed", [
                    'url' => $apiUrl,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();

            return $data ?? [];

        } catch (Exception $e) {
            Log::error("API request exception", [
                'url' => $apiUrl,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
