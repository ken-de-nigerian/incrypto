<?php

namespace App\Services;

use App\Events\ForexTradeExecuted;
use App\Models\Trade;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class ForexTradeService
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function executeForex(User $user, array $data)
    {
        $profile = $user->profile;

        // Check trading mode matches
        if ($data['trading_mode'] !== $profile->trading_status) {
            throw new Exception('Trading mode mismatch. Please refresh the page.');
        }

        // Get the appropriate balance
        $balance = $data['trading_mode'] === 'live'
            ? $profile->live_trading_balance
            : $profile->demo_trading_balance;

        if ($data['amount'] > $balance) {
            throw new Exception('Insufficient balance for this trade.');
        }

        $usedMargin = $balance * 0.15;
        $availableMargin = $balance - $usedMargin;

        if ($data['amount'] > $availableMargin) {
            throw new Exception('Insufficient margin available. Maximum: $' . number_format($availableMargin, 2));
        }

        // Calculate expiry time based on duration
        $expiryTime = $this->calculateExpiryTime($data['duration']);

        $execution = DB::transaction(function () use ($user, $data, $profile, $expiryTime) {

            $trade = Trade::create([
                'user_id' => $user->id,
                'pair' => $data['pair'],
                'pair_name' => $data['pair_name'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'duration' => $data['duration'],
                'entry_price' => $data['entry_price'],
                'trading_mode' => $data['trading_mode'],
                'status' => 'Open',
                'pnl' => 0,
                'expiry_time' => $expiryTime,
                'opened_at' => now()
            ]);

            if ($data['trading_mode'] === 'live') {
                $profile->decrement('live_trading_balance', $data['amount']);
            } else {
                $profile->decrement('demo_trading_balance', $data['amount']);
            }

            return $trade;
        });

        // Dispatch the event with the new transaction data
        event(new ForexTradeExecuted($user, $data, $expiryTime));

        return $execution;
    }

    /**
     * Calculate expiry time based on duration
     */
    private function calculateExpiryTime(string $duration): DateTime
    {
        $now = now();

        return match($duration) {
            '1m' => $now->addMinutes(),
            '5m' => $now->addMinutes(5),
            '15m' => $now->addMinutes(15),
            '30m' => $now->addMinutes(30),
            '1h' => $now->addHour(),
            '4h' => $now->addHours(4),
            '1d' => $now->addDay(),
            default => $now->addMinutes(5)
        };
    }
}
