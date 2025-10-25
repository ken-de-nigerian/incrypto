<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class StorePaymentGatewayService
{
    /**
     * @throws Exception|Throwable
     */
    public function store(array $validated): void
    {
        // Get the WALLET_ADDRESSES
        $wallets = config('gateways.wallet_addresses');

        // Ensure $wallets is an array
        if (!is_array($wallets)) {
            $wallets = [];
        }

        // Check if abbreviation already exists to ensure uniqueness
        foreach ($wallets as $wallet) {
            if (isset($wallet['abbreviation']) && $wallet['abbreviation'] === $validated['abbreviation']) {
                throw new Exception(
                    "Wallet abbreviation already exists."
                );
            }
        }

        $newWallet = [
            'method_code' => $this->uniqueid(),
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'coingecko_id' => $validated['coingecko_id'],
            'gateway_parameter' => $validated['gateway_parameter'],
            'status' => $validated['status'],
        ];

        $wallets[] = $newWallet;

        // Add a new wallet to all existing users inside transaction
        DB::transaction(function () use ($newWallet) {
            $this->addWalletToAllUsers($newWallet);
        });

        // Update the.env file only after users are successfully updated
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        // Update WALLET_ADDRESSES line
        $newEnvContent = preg_replace(
            '/^WALLET_ADDRESSES=.*/m',
            'WALLET_ADDRESSES=\'' . json_encode($wallets) . '\'',
            $envContent
        );

        File::put($envPath, $newEnvContent);

        try {
            Artisan::call('config:clear');
        } catch (Exception $e) {
            Log::warning('Failed to clear config cache: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception|Throwable
     */
    public function update(array $validated): void
    {
        // Get the WALLET_ADDRESSES
        $wallets = config('gateways.wallet_addresses');

        // Ensure $wallets is an array
        if (!is_array($wallets)) {
            $wallets = [];
        }

        // Find the wallet to update
        $walletIndex = null;
        $oldAbbreviation = null;

        foreach ($wallets as $index => $wallet) {
            if (isset($wallet['method_code']) && $wallet['method_code'] === $validated['method_code']) {
                $walletIndex = $index;
                $oldAbbreviation = $wallet['abbreviation'];
                break;
            }
        }

        if ($walletIndex === null) {
            throw new Exception("Wallet not found.");
        }

        // Check if new abbreviation already exists (excluding current wallet)
        foreach ($wallets as $wallet) {
            if (isset($wallet['abbreviation']) &&
                isset($wallet['method_code']) &&
                $wallet['abbreviation'] === $validated['abbreviation'] &&
                $wallet['method_code'] !== $validated['method_code']) {
                throw new Exception(
                    "Wallet abbreviation already exists."
                );
            }
        }

        $updatedWallet = [
            'method_code' => $validated['method_code'],
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'coingecko_id' => $validated['coingecko_id'],
            'gateway_parameter' => $validated['gateway_parameter'],
            'status' => $validated['status'],
        ];

        // Update wallet in array
        $wallets[$walletIndex] = $updatedWallet;

        // Update users' wallet_balance inside transaction
        DB::transaction(function () use ($updatedWallet, $oldAbbreviation) {
            $this->updateWalletForAllUsers($updatedWallet, $oldAbbreviation);
        });

        // Update .env file only after users are successfully updated
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        // Update WALLET_ADDRESSES line
        $newEnvContent = preg_replace(
            '/^WALLET_ADDRESSES=.*/m',
            'WALLET_ADDRESSES=\'' . json_encode($wallets) . '\'',
            $envContent
        );

        File::put($envPath, $newEnvContent);

        try {
            Artisan::call('config:clear');
        } catch (Exception $e) {
            Log::warning('Failed to clear config cache: ' . $e->getMessage());
        }
    }

    /**
     * Extract the path and query string from a full image URL.
     *
     * @param string $url
     * @return string
     */
    private function extractImagePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        // Append query string if it exists
        return $query ? $path . '?' . $query : $path;
    }

    /**
     * Get crypto image from coingecko_id
     *
     * @param string $coingeckoId
     * @return string|null
     */
    private function getCryptoImage(string $coingeckoId): ?string
    {
        try {
            $gatewayService = new GatewayHandlerService();
            $cryptos = $gatewayService->getCryptos();

            foreach ($cryptos as $crypto) {
                if ($crypto['id'] === $coingeckoId) {
                    $image = $crypto['image'] ?? null;
                    return $image ? $this->extractImagePath($image) : null;
                }
            }
        } catch (Exception $e) {
            Log::warning("Failed to fetch crypto image for $coingeckoId: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Add the new wallet to all existing users' wallet_balance using chunking
     *
     * @param array $newWallet
     * @return void
     * @throws Exception
     */
    private function addWalletToAllUsers(array $newWallet): void
    {
        try {
            User::chunk(1000, function ($users) use ($newWallet) {
                foreach ($users as $user) {
                    $this->addWalletToUser($user, $newWallet);
                }
            });
        } catch (Exception $e) {
            Log::error('Failed to add wallet to users: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update wallet for all existing users' wallet_balance using chunking
     *
     * @param array $updatedWallet
     * @param string $oldAbbreviation
     * @return void
     * @throws Exception
     */
    private function updateWalletForAllUsers(array $updatedWallet, string $oldAbbreviation): void
    {
        try {
            User::chunk(1000, function ($users) use ($updatedWallet, $oldAbbreviation) {
                foreach ($users as $user) {
                    $this->updateWalletForUser($user, $updatedWallet, $oldAbbreviation);
                }
            });
        } catch (Exception $e) {
            Log::error('Failed to update wallet for users: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add a new wallet to a single user's wallet_balance
     *
     * @param User $user
     * @param array $newWallet
     * @return void
     * @throws Exception
     */
    private function addWalletToUser(User $user, array $newWallet): void
    {
        try {
            // Get the user's current wallet balance
            $walletBalance = $user->wallet_balance;

            // Decode the JSON
            $wallets = is_string($walletBalance)
                ? json_decode($walletBalance, true)
                : (is_array($walletBalance) ? $walletBalance : []);

            // Create the key using abbreviation (e.g., "BTC", "ETH")
            $key = $newWallet['abbreviation'];

            // Check if wallet already exists for this user
            if (isset($wallets[$key])) {
                return;
            }

            // Get the image for this wallet
            $image = $this->getCryptoImage($newWallet['coingecko_id']);

            // Add the new wallet with initial balance of 0
            $wallets[$key] = [
                'id' => $newWallet['method_code'],
                'name' => $newWallet['name'],
                'symbol' => $newWallet['abbreviation'],
                'network' => $newWallet['gateway_parameter'],
                'balance' => 0,
                'status' => $newWallet['status'],
                'image' => $image,
            ];

            // Update user's wallet_balance
            $user->update([
                'wallet_balance' => json_encode($wallets),
            ]);
        } catch (Exception $e) {
            Log::error("Failed to add wallet to user $user->id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update wallet for a single user's wallet_balance
     *
     * @param User $user
     * @param array $updatedWallet
     * @param string $oldAbbreviation
     * @return void
     * @throws Exception
     */
    private function updateWalletForUser(User $user, array $updatedWallet, string $oldAbbreviation): void
    {
        try {
            // Get the user's current wallet balance
            $walletBalance = $user->wallet_balance;

            // Decode the JSON
            $wallets = is_string($walletBalance)
                ? json_decode($walletBalance, true)
                : (is_array($walletBalance) ? $walletBalance : []);

            // Get the image for this wallet
            $image = $this->getCryptoImage($updatedWallet['coingecko_id']);

            // If abbreviation changed, remove old key and add new one
            if ($oldAbbreviation !== $updatedWallet['abbreviation']) {
                if (isset($wallets[$oldAbbreviation])) {
                    $wallets[$updatedWallet['abbreviation']] = [
                        'id' => $updatedWallet['method_code'],
                        'name' => $updatedWallet['name'],
                        'symbol' => $updatedWallet['abbreviation'],
                        'network' => $updatedWallet['gateway_parameter'],
                        'balance' => $wallets[$oldAbbreviation]['balance'],
                        'status' => $updatedWallet['status'],
                        'image' => $image,
                    ];
                    unset($wallets[$oldAbbreviation]);
                }
            } else {
                // Only update the wallet details, preserve balance
                $key = $updatedWallet['abbreviation'];
                if (isset($wallets[$key])) {
                    $wallets[$key] = [
                        'id' => $updatedWallet['method_code'],
                        'name' => $updatedWallet['name'],
                        'symbol' => $updatedWallet['abbreviation'],
                        'network' => $updatedWallet['gateway_parameter'],
                        'balance' => $wallets[$key]['balance'],
                        'status' => $updatedWallet['status'],
                        'image' => $image,
                    ];
                }
            }

            // Update user's wallet_balance
            $user->update([
                'wallet_balance' => json_encode($wallets),
            ]);
        } catch (Exception $e) {
            Log::error("Failed to update wallet for user $user->id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a unique ID
     *
     * This method generates a unique ID, which can be used for various purposes.
     *
     * @return string The generated unique ID.
     */
    private function uniqueid(): string
    {
        // Generate a unique ID based on the current timestamp and a random number
        return substr(number_format(time() * rand(), 0, '', ''), 0, 12);
    }
}
