<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use JsonException;

class WalletService
{
    protected GatewayHandlerService $gatewayHandler;

    private User $user;
    private array $balances;
    private array $fullWalletData;

    public function __construct(User $user, GatewayHandlerService $gatewayHandler)
    {
        $this->user = $user;
        $this->fullWalletData = $this->decodeFullWalletData();
        $this->balances = $this->extractBalances();
        $this->gatewayHandler = $gatewayHandler;
    }

    private function decodeFullWalletData(): array
    {
        try {
            return json_decode($this->user->wallet_balance ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error("Wallet Balance JSON Decode Error for User ID {$this->user->id}: " . $e->getMessage());
            return [];
        }
    }

    private function extractBalances(): array
    {
        $balances = [];
        foreach ($this->fullWalletData as $symbol => $data) {
            $balances[strtoupper($symbol)] = (float)($data['balance'] ?? 0);
        }
        return $balances;
    }

    public function getFullWalletData(): array
    {
        return $this->fullWalletData;
    }

    public function getBalances(): array
    {
        return $this->balances;
    }

    public function getBalance(string $symbol): float
    {
        return $this->balances[strtoupper($symbol)] ?? 0;
    }

    public function hasSufficientBalance(string $symbol, float $amount): bool
    {
        return $this->getBalance(strtoupper($symbol)) >= $amount;
    }

    /**
     * @throws Exception
     */
    public function debit(string $symbol, float $amount): void
    {
        $symbol = strtoupper($symbol);
        if (!$this->hasSufficientBalance($symbol, $amount)) {
            throw new Exception("Attempted to debit more than available for $symbol.");
        }
        $this->balances[$symbol] -= $amount;
    }

    public function credit(string $symbol, float $amount): void
    {
        $symbol = strtoupper($symbol);
        if (!isset($this->balances[$symbol])) {
            $this->balances[$symbol] = 0;
            $this->fullWalletData[$symbol] = ['balance' => 0, 'chain' => 'Unknown'];
        }
        $this->balances[$symbol] += $amount;
    }

    public function save(): void
    {
        foreach ($this->balances as $symbol => $balance) {
            if (isset($this->fullWalletData[$symbol])) {
                $this->fullWalletData[$symbol]['balance'] = $balance;
            }
        }

        $this->user->wallet_balance = json_encode($this->fullWalletData);
        $this->user->save();
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
