<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class WalletService
{
    protected GatewayHandlerService $gatewayHandler;

    public function __construct(GatewayHandlerService $gatewayHandler)
    {
        $this->gatewayHandler = $gatewayHandler;
    }

    /**
     * Initialize a new wallet with available cryptocurrencies.
     *
     * @return string|false JSON encoded wallet data or false on failure.
     */
    public function initializeNewUserWallet(): string|false
    {
        try {
            $cryptocurrencies = $this->gatewayHandler->getGateways();
            $formattedCryptos = [];

            foreach ($cryptocurrencies as $crypto) {
                $symbol = strtoupper($crypto['abbreviation']);
                $name = $crypto['name'];
                $key = $symbol;

                if (str_contains($name, 'TRC 20')) $key = 'USDT_TRC20';
                elseif (str_contains($name, 'BEP 20')) $key = 'USDT_BEP20';

                $formattedCryptos[$key] = [
                    'name' => $name,
                    'symbol' => $symbol,
                    'network' => $this->getNetworkFromName($name),
                    'balance' => 0.00,
                    'image' => $crypto['image'] ?? null,
                ];
            }

            return json_encode($formattedCryptos);
        } catch (Exception $e) {
            Log::error(__('Wallet creation failed'), ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Extract network type from cryptocurrency name.
     */
    private function getNetworkFromName(string $name): ?string
    {
        if (str_contains($name, 'TRC 20')) return 'TRC20';
        if (str_contains($name, 'BEP 20')) return 'BEP20';
        if (str_contains($name, 'ERC 20')) return 'ERC20';
        return null;
    }
}
