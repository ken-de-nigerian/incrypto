<?php

namespace App\Services;

use App\Events\BalanceAdjusted;
use App\Models\ReceivedCrypto;
use App\Models\SendCrypto;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdjustUserBalanceService
{
    public function __construct(
        protected MarketDataService $marketDataService,
        protected GatewayHandlerService $gatewayHandler
    ) {
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function updateUserBalance(User $user, array $data)
    {
        $reason = $data['reason'] ?? null;
        $actionType = $data['actionType'];
        $token = strtoupper($data['wallet_symbol']);
        $amount = (float) $data['amount'];
        $baseToken = $this->marketDataService->getBaseSymbol($token);

        if (!$this->marketDataService->isValidToken($baseToken)) {
            throw new Exception('Invalid token provided.');
        }

        $walletService = new WalletService($user, $this->gatewayHandler);

        $result = DB::transaction(function () use ($user, $walletService, $token, $amount, $data) {

            if ($data['actionType'] == 'debit') {
                if (!$walletService->hasSufficientBalance($token, $amount)) {
                    $currentBalance = $walletService->getBalance($token);
                    throw new Exception(
                        "Cannot debit $amount $token. The wallet only has $currentBalance $token available. " .
                        "Please reduce the amount or select a different wallet."
                    );
                }

                $walletService->debit($token, $amount);
                $walletService->save();

                return SendCrypto::create([
                    'user_id' => $user->id,
                    'token_symbol' => $data['wallet_symbol'],
                    'recipient_address' => '0x' . bin2hex(random_bytes(36)),
                    'amount' => $data['amount'],
                    'status' => 'completed',
                    'transaction_hash' => '0x' . bin2hex(random_bytes(32)),
                    'fee' => 0.00,
                ]);
            }

            $walletService->credit($token, $amount);
            $walletService->save();

            return ReceivedCrypto::create([
                'user_id' => $user->id,
                'token_symbol' => $data['wallet_symbol'],
                'wallet_address' => '0x' . bin2hex(random_bytes(36)),
                'amount' => $data['amount'],
                'status'         => 'completed',
                'transaction_hash' => '0x' . bin2hex(random_bytes(32)),
            ]);
        });

        // Dispatch the event with the new transaction data
        event(new BalanceAdjusted($user, $token, $amount, $reason, $actionType));

        return $result;
    }
}
