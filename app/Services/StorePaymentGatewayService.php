<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletAddress;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StorePaymentGatewayService
{
    /**
     * @throws Exception|Throwable
     */
    public function store(array $validated): void
    {
        $newWallet = [
            'method_code' => $this->generateUniqueId(),
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'coingecko_id' => $validated['coingecko_id'],
            'gateway_parameter' => $validated['gateway_parameter'],
            'status' => $validated['status'],
        ];

        // Perform operations in transaction to ensure consistency
        DB::transaction(function () use ($newWallet) {
            // Create wallet in a database
            WalletAddress::create($newWallet);

            // Add wallet to all users
            $this->addWalletToAllUsers($newWallet);
        });
    }

    /**
     * @throws Exception|Throwable
     */
    public function update(array $validated): void
    {
        // Find the wallet to update
        $walletAddress = WalletAddress::where('method_code', $validated['method_code'])->first();

        if (!$walletAddress) {
            throw new Exception("Wallet not found.");
        }

        $oldAbbreviation = $walletAddress->abbreviation;

        $updatedWallet = [
            'method_code' => $validated['method_code'],
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'coingecko_id' => $validated['coingecko_id'],
            'gateway_parameter' => $validated['gateway_parameter'],
            'status' => $validated['status'],
        ];

        // Perform operations in transaction to ensure consistency
        DB::transaction(function () use ($walletAddress, $updatedWallet, $oldAbbreviation) {
            // Update wallet in database
            $walletAddress->update($updatedWallet);

            // Update wallet for all users
            $this->updateWalletForAllUsers($updatedWallet, $oldAbbreviation);
        });
    }

    /**
     * Delete a wallet
     *
     * @param string $methodCode
     * @return void
     * @throws Exception|Throwable
     */
    public function delete(string $methodCode): void
    {
        $walletAddress = WalletAddress::where('method_code', $methodCode)->first();

        if (!$walletAddress) {
            throw new Exception("Wallet not found.");
        }

        DB::transaction(function () use ($walletAddress) {
            // Remove wallet from all users
            $this->removeWalletFromAllUsers($walletAddress->toArray());

            // Delete from database
            $walletAddress->delete();
        });
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
     * Generate wallet key based on name and network
     *
     * @param string $name
     * @param string $abbreviation
     * @return string
     */
    private function generateWalletKey(string $name, string $abbreviation): string
    {
        if (str_contains($name, 'TRC20') || str_contains($name, 'TRC 20')) {
            return trim(str_replace(['TRC20', 'TRC 20'], '', $name)) . '_TRC20';
        } elseif (str_contains($name, 'ERC20') || str_contains($name, 'ERC 20')) {
            return trim(str_replace(['ERC20', 'ERC 20'], '', $name)) . '_ERC20';
        } elseif (str_contains($name, 'BEP20') || str_contains($name, 'BEP 20')) {
            return trim(str_replace(['BEP20', 'BEP 20'], '', $name)) . '_BEP20';
        }

        return $abbreviation;
    }

    /**
     * Extract network type from cryptocurrency name.
     *
     * @param string $name
     * @return string|null
     */
    private function getNetworkFromName(string $name): ?string
    {
        if (str_contains($name, 'TRC 20')) return 'TRC20';
        if (str_contains($name, 'BEP 20')) return 'BEP20';
        if (str_contains($name, 'ERC 20')) return 'ERC20';
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
     * Remove wallet from all existing users' wallet_balance using chunking
     *
     * @param array $wallet
     * @return void
     * @throws Exception
     */
    private function removeWalletFromAllUsers(array $wallet): void
    {
        try {
            User::chunk(1000, function ($users) use ($wallet) {
                foreach ($users as $user) {
                    $this->removeWalletFromUser($user, $wallet);
                }
            });
        } catch (Exception $e) {
            Log::error('Failed to remove wallet from users: ' . $e->getMessage());
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
            $walletBalance = $user->wallet_balance;
            $wallets = is_string($walletBalance)
                ? json_decode($walletBalance, true)
                : (is_array($walletBalance) ? $walletBalance : []);

            if (!is_array($wallets)) {
                $wallets = [];
            }

            $key = $this->generateWalletKey($newWallet['name'], $newWallet['abbreviation']);
            $image = $this->getCryptoImage($newWallet['coingecko_id']);

            // Check if wallet already exists
            if (isset($wallets[$key])) {
                Log::info("Wallet key '$key' already exists for user $user->id");
                return;
            }

            $wallets[$key] = [
                'id' => $newWallet['method_code'],
                'name' => $newWallet['name'],
                'symbol' => $newWallet['abbreviation'],
                'network' => $this->getNetworkFromName($newWallet['name']),
                'balance' => 0,
                'status' => $newWallet['status'],
                'image' => $image,
            ];

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
            $walletBalance = $user->wallet_balance;
            $wallets = is_string($walletBalance)
                ? json_decode($walletBalance, true)
                : (is_array($walletBalance) ? $walletBalance : []);

            if (!is_array($wallets)) {
                $wallets = [];
            }

            $image = $this->getCryptoImage($updatedWallet['coingecko_id']);
            $newKey = $this->generateWalletKey($updatedWallet['name'], $updatedWallet['abbreviation']);

            // Find wallet by method_code (id)
            $oldKey = null;
            $currentBalance = 0;

            foreach ($wallets as $key => $wallet) {
                if (isset($wallet['id']) && $wallet['id'] === $updatedWallet['method_code']) {
                    $oldKey = $key;
                    $currentBalance = $wallet['balance'] ?? 0;
                    break;
                }
            }

            if ($oldKey === null) {
                Log::warning("Wallet with method_code '{$updatedWallet['method_code']}' not found for user $user->id");
                return;
            }

            // Remove old key if different from new key
            if ($oldKey !== $newKey) {
                unset($wallets[$oldKey]);
            }

            // Add/update with new key
            $wallets[$newKey] = [
                'id' => $updatedWallet['method_code'],
                'name' => $updatedWallet['name'],
                'symbol' => $updatedWallet['abbreviation'],
                'network' => $this->getNetworkFromName($updatedWallet['name']),
                'balance' => $currentBalance,
                'status' => $updatedWallet['status'],
                'image' => $image,
            ];

            $user->update([
                'wallet_balance' => json_encode($wallets),
            ]);
        } catch (Exception $e) {
            Log::error("Failed to update wallet for user $user->id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove wallet from a single user's wallet_balance
     *
     * @param User $user
     * @param array $wallet
     * @return void
     * @throws Exception
     */
    private function removeWalletFromUser(User $user, array $wallet): void
    {
        try {
            $walletBalance = $user->wallet_balance;
            $wallets = is_string($walletBalance)
                ? json_decode($walletBalance, true)
                : (is_array($walletBalance) ? $walletBalance : []);

            if (!is_array($wallets)) {
                return;
            }

            // Find and remove wallet by method_code (id)
            foreach ($wallets as $key => $userWallet) {
                if (isset($userWallet['id']) && $userWallet['id'] === $wallet['method_code']) {
                    unset($wallets[$key]);
                    break;
                }
            }

            $user->update([
                'wallet_balance' => json_encode($wallets),
            ]);
        } catch (Exception $e) {
            Log::error("Failed to remove wallet from user $user->id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a unique ID using secure random generation
     *
     * @return string
     */
    private function generateUniqueId(): string
    {
        // Use uniqid with more entropy for better collision avoidance
        return uniqid('gateway_', true);
    }
}
