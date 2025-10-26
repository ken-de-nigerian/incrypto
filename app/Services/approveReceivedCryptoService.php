<?php

namespace App\Services;

use App\Events\BalanceAdjusted;
use App\Models\ReceivedCrypto;
use Exception;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

class approveReceivedCryptoService
{
    public function __construct(
        protected MarketDataService $marketDataService,
        protected GatewayHandlerService $gatewayHandler
    ) {
    }

    /**
     * @throws RandomException
     * @throws Throwable
     */
    public function approve(string $id, string $amount)
    {
        $crypto = ReceivedCrypto::with('user')->findOrFail($id);
        $user = $crypto->user;
        $token = strtoupper($crypto->token_symbol);
        $amount = (float) $amount;
        $baseToken = $this->marketDataService->getBaseSymbol($token);
        $reason = '';
        $actionType = 'credit';

        if (!$this->marketDataService->isValidToken($baseToken)) {
            throw new Exception('Invalid token provided.');
        }

        $walletService = new WalletService($user, $this->gatewayHandler);

        $result = DB::transaction(function () use ($user, $walletService, $token, $amount, $crypto) {
            $walletService->credit($token, $amount);
            $walletService->save();

            return $crypto->update([
                'amount' => $amount,
                'transaction_hash' => '0x' . bin2hex(random_bytes(12)),
                'status' => 'completed'
            ]);
        });

        // Dispatch the event with the new transaction data
        event(new BalanceAdjusted($user, $token, $amount, $reason, $actionType));

        return $result;
    }
}
