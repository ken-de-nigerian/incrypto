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
    ];

    /**
     * Retry logic constants
     */
    private const MAX_RETRIES = 3;
    private const RETRY_BASE_DELAY = 1;
    private const RATE_LIMIT_RETRY_DELAY = 10;

    /**
     * Chain ID mapping
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

                if (($response['status'] ?? '0') !== '1' || !isset($response['result'])) {
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

        $response = $this->fetchData(
            $cacheKey,
            $url,
            self::CACHE_TTL['PRICE_DATA'],
            'Failed to fetch ETH price from CoinGecko'
        );

        return $response['ethereum']['usd'] ?? 2000.0;
    }

    /**
     * Fetch CoinGecko market data for specific coin IDs.
     *
     * @param array $coinIds
     * @return array
     */
    private function fetchCoinGeckoMarketData(array $coinIds): array
    {
        if (empty($coinIds)) {
            return [];
        }

        $cacheKey = "coinGeckoSpecificCoins_" . implode('_', $coinIds);
        $ids = implode(',', $coinIds);
        $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=$ids&order=market_cap_desc&sparkline=false";

        return $this->fetchData(
            $cacheKey,
            $url,
            self::CACHE_TTL['PRICE_DATA'],
            'Failed to fetch CoinGecko market data for specific IDs'
        );
    }

    /**
     * Fetch crypto data for active gateways efficiently.
     * This now makes only ONE API call instead of two.
     *
     * @return array
     */
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
            Log::warning('Received empty market data from CoinGecko.', ['ids' => $coinIds]);
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
     *
     * @param array $wallets
     * @return array
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
            $response = $this->fetchFromAPIWithRetry($apiUrl);

            if ($response['error'] ?? false) {
                Log::warning("API fetch failed after all retries.", [
                    'context' => $errorContext,
                    'url' => $apiUrl,
                    'final_status' => $response['status'] ?? 'N/A'
                ]);
                return [];
            }

            return $response['data'] ?? [];
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
        $response = [];

        while ($retries < self::MAX_RETRIES) {
            $response = $this->makeApiRequest($apiUrl);

            if ($response['status'] >= 200 && $response['status'] < 300) {
                return ['error' => false, 'data' => $response['data']];
            }

            Log::warning("API request attempt failed", [
                'url' => $apiUrl,
                'attempt' => $retries + 1,
                'status' => $response['status']
            ]);

            $retries++;
            if ($retries < self::MAX_RETRIES) {
                $delay = ($response['status'] === 429)
                    ? self::RATE_LIMIT_RETRY_DELAY
                    : self::RETRY_BASE_DELAY * pow(2, $retries - 1);

                Log::debug("Retrying API request after delay.", ['url' => $apiUrl, 'delay' => $delay]);
                sleep($delay);
            }
        }

        return ['error' => true, 'status' => $response['status'] ?? 500, 'data' => []];
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
