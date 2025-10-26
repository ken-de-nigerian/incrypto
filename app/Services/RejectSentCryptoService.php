<?php

namespace App\Services;

use App\Models\SendCrypto;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class RejectSentCryptoService
{
    public function __construct(
        protected MarketDataService $marketDataService,
        protected GatewayHandlerService $gatewayHandler
    ) {
    }

    /**
     * @throws Throwable
     */
    public function reject(string $id)
    {
        $crypto = SendCrypto::with('user')->findOrFail($id);
        $user = $crypto->user;
        $token = strtoupper($crypto->token_symbol);
        $amount = (float) $crypto->amount + (float) $crypto->fee;
        $baseToken = $this->marketDataService->getBaseSymbol($token);

        if (!$this->marketDataService->isValidToken($baseToken)) {
            throw new Exception('Invalid token provided.');
        }

        $walletService = new WalletService($user, $this->gatewayHandler);

        return DB::transaction(function () use ($user, $walletService, $token, $amount, $crypto) {

            $walletService->credit($token, $amount);
            $walletService->save();

            return $crypto->update([
                'transaction_hash' => '0x' . bin2hex(random_bytes(12)),
                'status' => 'failed'
            ]);
        });
    }
}
