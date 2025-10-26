<?php

namespace App\Services;

use App\Events\BalanceAdjusted;
use App\Models\SendCrypto;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

class ApproveSentCryptoService
{
    /**
     * @throws RandomException
     * @throws Throwable
     */
    public function approve(string $id)
    {
        $crypto = SendCrypto::with('user')->findOrFail($id);
        $user = $crypto->user;
        $token = strtoupper($crypto->token_symbol);
        $amount = (float) $crypto->amount;
        $reason = '';
        $actionType = 'debit';

        $result = DB::transaction(function () use ($crypto) {
            return $crypto->update([
                'transaction_hash' => '0x' . bin2hex(random_bytes(12)),
                'status' => 'completed'
            ]);
        });

        // Dispatch the event with the new transaction data
        event(new BalanceAdjusted($user, $token, $amount, $reason, $actionType));

        return $result;
    }
}
