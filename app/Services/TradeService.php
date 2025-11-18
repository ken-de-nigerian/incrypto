<?php

namespace App\Services;

use App\Events\InvestmentExecuted;
use App\Events\TradeClosed;
use App\Events\TradeExecuted;
use App\Models\InvestmentHistory;
use App\Models\Plan;
use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class TradeService
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function executeTrade(User $user, array $data)
    {
        $expiryTime = $this->calculateExpiryTime($data['duration']);

        $shouldWin = false;
        if ($data['trading_mode'] === 'demo') {
            $shouldWin = true;
        }

        $execution = DB::transaction(function () use ($user, $data, $expiryTime, $shouldWin) {

            $profile = $user->profile()->lockForUpdate()->first();
            if ($data['trading_mode'] !== $profile->trading_status) {
                throw new Exception('Trading mode mismatch. Please refresh the page.');
            }

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

            $trade = Trade::create([
                'user_id' => $user->id,
                'category' => $data['category'],
                'pair' => $data['pair'],
                'pair_name' => $data['pair_name'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'leverage' => $data['leverage'],
                'duration' => $data['duration'],
                'entry_price' => $data['entry_price'],
                'trading_mode' => $data['trading_mode'],
                'is_demo_forced_win' => $shouldWin,
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
        event(new TradeExecuted($user, $data, $expiryTime));

        return $execution;
    }

    /**
     * @throws Throwable
     */
    public function closeTrade(User $user, array $data, Trade $trade)
    {
        $isAutoClose = $data['is_auto_close'] ?? false;

        $closed = DB::transaction(function () use ($user, $data, $trade, $isAutoClose) {

            $trade->lockForUpdate();
            $trade->refresh();

            if ($trade->status !== 'Open') {
                return false;
            }

            $updated = $trade->update([
                'exit_price' => $data['exit_price'],
                'pnl' => $data['pnl'],
                'status' => 'Closed',
                'closed_at' => $data['closed_at'],
                'is_auto_close' => $isAutoClose
            ]);

            $balanceField = $trade->trading_mode === 'live'
                ? 'live_trading_balance'
                : 'demo_trading_balance';

            $user->profile->increment($balanceField, $trade->amount + $data['pnl']);

            return $updated;
        });

        if ($closed) {
            event(new TradeClosed($user, $trade));
        }

        return $closed;
    }

    /**
     * @throws Throwable
     */
    public function executeInvestment(User $user, array $data)
    {
        $execution = DB::transaction(function () use ($user, $data) {

            // Get Plan with relations
            $plan = Plan::with('plan_time_settings')
                ->findOrFail($data['plan_id']);

            // Verify plan is active
            if ($plan->status !== 'active') {
                throw new Exception('This investment plan is not currently active.');
            }

            // Verify user is in live mode
            if ($user->profile->trading_status !== 'live') {
                throw new Exception('Investments can only be made in Live Mode.');
            }

            // Get live balance
            $liveBalance = is_string($user->profile->live_trading_balance)
                ? (float) $user->profile->live_trading_balance
                : $user->profile->live_trading_balance;

            // Verify sufficient balance
            if ($data['amount'] > $liveBalance) {
                throw new Exception('Insufficient live trading balance.');
            }

            // Verify amount is within plan limits
            if ($data['amount'] < $plan->minimum || $data['amount'] > $plan->maximum) {
                throw new Exception('Investment amount must be between $' . number_format($plan->minimum, 2) . ' and $' . number_format($plan->maximum, 2) . '.');
            }

            // Calculate interest
            $interest = $plan->interest * $plan->repeat_time;
            $final_interest = ($interest * $data['amount']) / 100;

            // Calculate next_time
            $now = Carbon::now()->toDateTimeString();
            $nextTime = Carbon::parse($now)->addHours($plan->period)->toDateTimeString();

            // Create investment record
            $investment = InvestmentHistory::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $data['amount'],
                'interest' => $final_interest,
                'period' => $plan->plan_time_settings->period ?? $plan->period,
                'repeat_time' => $plan->repeat_time,
                'next_time' => $nextTime,
                'status' => 'running',
                'capital_back_status' => $plan->capital_back_status,
            ]);

            // Deduct amount from live trading balance
            $user->profile()->decrement('live_trading_balance', $data['amount']);

            return $investment;
        });

        // Dispatch the event with the investment data
        event(new InvestmentExecuted($user, $data, $execution));

        return $execution;
    }

    /**
     * Calculate expiry time based on duration
     */
    private function calculateExpiryTime(string $duration): DateTime
    {
        return match($duration) {
            '1m' => now()->addMinute(),
            '5m' => now()->addMinutes(5),
            '15m' => now()->addMinutes(15),
            '30m' => now()->addMinutes(30),
            '1h' => now()->addHour(),
            '4h' => now()->addHours(4),
            '1d' => now()->addDay(),
            default => now()->addMinutes(5)
        };
    }
}
