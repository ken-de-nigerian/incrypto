<?php

namespace App\Services;

use App\Events\CopyTradeClosed;
use App\Events\CopyTradeExecuted;
use App\Events\CopyTradeStarted;
use App\Events\InvestmentExecuted;
use App\Events\InvestmentPayout;
use App\Events\TradeClosed;
use App\Events\TradeExecuted;
use App\Models\CopyTrade;
use App\Models\CopyTradeTransaction;
use App\Models\InvestmentHistory;
use App\Models\MasterTrader;
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

            // Create the Master's Trade
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

            // Deduct Master's Balance
            if ($data['trading_mode'] === 'live') {

                $profile->decrement('live_trading_balance', $data['amount']);

                // Find the MasterTrader record for this user
                $isMasterTrader = $user->isMasterTrader();
                $masterTrader = $user->masterTrader()->lockForUpdate()->first();

                if ($isMasterTrader) {

                    // Update Master Trader Stats: Increment total trades
                    $masterTrader->increment('total_trades');

                    // Fetch active copiers
                    $activeCopies = CopyTrade::where('master_trader_id', $masterTrader->id)
                        ->where('status', 'active')
                        ->with(['user.profile'])
                        ->get();

                    foreach ($activeCopies as $copyTrade) {
                        $copierUser = $copyTrade->user;

                        // Amount copied
                        $copyAmount = $data['amount'];

                        // Check Copier's Balance
                        $copierBalance = $copierUser->profile->live_trading_balance;

                        if ($copierBalance >= $copyAmount) {

                            // Deduct balance from Copier
                            $copierUser->profile()->decrement('live_trading_balance', $copyAmount);

                            // Map the trade type
                            $transactionType = match(strtolower($data['type'])) {
                                'buy', 'long', 'up', 'call' => 'up',
                                default => 'down',
                            };

                            // Create Transaction Record
                            $transaction = CopyTradeTransaction::create([
                                'copy_trade_id' => $copyTrade->id,
                                'type' => $transactionType,
                                'amount' => $copyAmount,
                                'description' => "Copied trade on {$data['pair_name']}",
                                'metadata' => $trade->toArray(),
                            ]);

                            // Notify the copier
                            event(new CopyTradeExecuted($copierUser, $transaction));
                        }
                    }
                }
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

        $execution = DB::transaction(function () use ($user, $data, $trade, $isAutoClose) {

            $trade->lockForUpdate();
            $trade->refresh();

            if ($trade->status !== 'Open') {
                return false;
            }

            // Close the Master Trade
            $updated = $trade->update([
                'exit_price' => $data['exit_price'],
                'pnl' => $data['pnl'],
                'status' => 'Closed',
                'closed_at' => $data['closed_at'],
                'is_auto_close' => $isAutoClose
            ]);

            // Update Master Balance
            $balanceField = $trade->trading_mode === 'live'
                ? 'live_trading_balance'
                : 'demo_trading_balance';

            $user->profile->increment($balanceField, $trade->amount + $data['pnl']);

            // Handle Master Trader Stats & Copiers (Only for Live Trades)
            if ($trade->trading_mode === 'live') {

                $isMasterTrader = $user->isMasterTrader();
                $masterTrader = $user->masterTrader()->lockForUpdate()->first();

                if ($isMasterTrader) {
                    if ($data['pnl'] >= 0) {
                        $masterTrader->increment('total_profit', $data['pnl']);
                    } else {
                        $masterTrader->increment('total_loss', abs($data['pnl']));
                    }

                    // Recalculate Stats
                    $allTrades = Trade::where('user_id', $user->id)
                        ->where('trading_mode', 'live')
                        ->where('status', 'Closed')
                        ->get();

                    $totalTrades = $allTrades->count();
                    $winningTrades = $allTrades->where('pnl', '>=', 0)->count();
                    $losingTrades = $totalTrades - $winningTrades;

                    // Win Rate
                    $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

                    // Gain Percentage (Total PnL / Total Invested)
                    $totalPnl = $allTrades->sum('pnl');
                    $totalInvested = $allTrades->sum('amount');
                    $gainPercentage = $totalInvested > 0 ? ($totalPnl / $totalInvested) * 100 : 0;

                    // Risk Score Calculation (1-10)
                    $riskScore = 1;
                    if ($totalTrades > 0) {
                        $lossRate = $losingTrades / $totalTrades;
                        $calculatedRisk = ceil($lossRate * 10);
                        $riskScore = max(1, min(10, $calculatedRisk));
                    }

                    $masterTrader->update([
                        'win_rate' => $winRate,
                        'gain_percentage' => $gainPercentage,
                        'risk_score' => $riskScore
                    ]);
                }

                // Find corresponding transactions for copiers using metadata->id
                $copyTransactions = CopyTradeTransaction::where('metadata->id', $trade->id)->get();

                foreach ($copyTransactions as $transaction) {
                    $copyTrade = $transaction->copyTrade;
                    $copierUser = $copyTrade->user;

                    if (!$copyTrade || !$copierUser) continue;

                    // Calculate Copier PnL: (Copier Amount / Master Amount) * Master PnL
                    $copierPnl = 0.0;
                    if ($trade->amount > 0) {
                        // Use high precision for calculation
                        $copierPnl = ((float)$transaction->amount / (float)$trade->amount) * (float)$data['pnl'];
                    }

                    // Update Copier Balance
                    $copierUser->profile()->increment('live_trading_balance', $transaction->amount + $copierPnl);

                    // Update CopyTrade Stats (Cumulative)
                    if ($copierPnl >= 0) {
                        $copyTrade->increment('current_profit', $copierPnl);
                    } else {
                        $copyTrade->increment('current_loss', abs($copierPnl));
                    }

                    // Update Transaction Metadata with Close Details
                    $metadata = $transaction->metadata ?? [];
                    $metadata['exit_price'] = $data['exit_price'];

                    // Store PnL without number_format to preserve precision
                    $metadata['pnl'] = $copierPnl;

                    $metadata['status'] = 'Closed';
                    $metadata['closed_at'] = $data['closed_at'];

                    // We force the update to ensure JSON encoding picks up the float type correctly
                    $transaction->update([
                        'metadata' => $metadata
                    ]);

                    // Notify the copier that their copied trade has closed
                    event(new CopyTradeClosed($copierUser, $transaction));
                }
            }

            return $updated;
        });

        if ($execution) {
            event(new TradeClosed($user, $trade));
        }

        return $execution;
    }

    /**
     * Execute a new investment
     *
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

            // Calculate interest PER CYCLE
            $interestPerCycle = ($plan->interest * $data['amount']) / 100;

            // Calculate next_time
            $now = Carbon::now();
            $nextTime = $now->copy()->addHours($plan->period);

            // Create investment record
            $investment = InvestmentHistory::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $data['amount'],
                'interest' => $interestPerCycle,
                'period' => $plan->plan_time_settings->period ?? $plan->period,
                'repeat_time' => $plan->repeat_time,
                'repeat_time_count' => 0,
                'next_time' => $nextTime->toDateTimeString(),
                'last_time' => $now->toDateTimeString(),
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
     * @throws Throwable
     */
    public function startCopy(MasterTrader $masterTrader, User $user, array $data)
    {
        $execution = DB::transaction(function () use ($user, $masterTrader, $data) {

            // Check if the user is already copying this Master Trader
            $alreadyCopying = $user->copyTrades()
                ->where('master_trader_id', $masterTrader->id)
                ->whereIn('status', ['active', 'paused'])
                ->exists();

            if ($alreadyCopying) {
                throw new Exception('You are already copying this Master Trader.');
            }

            // Verify user is in live mode
            if ($user->profile->trading_status !== 'live') {
                throw new Exception('You must be in live trading mode to copy traders.');
            }

            // Get live balance
            $liveBalance = is_string($user->profile->live_trading_balance)
                ? (float) $user->profile->live_trading_balance
                : $user->profile->live_trading_balance;

            // Verify sufficient balance
            if ($data['amount'] > $liveBalance) {
                throw new Exception('Insufficient balance.');
            }

            // Create copy trade
            $copyTrade = $user->copyTrades()->create([
                'master_trader_id' => $masterTrader->id,
                'total_commission_paid' => $data['amount'],
                'status' => 'active',
                'started_at' => now(),
            ]);

            // Deduct amount from live trading balance
            $user->profile->update([
                'live_trading_balance' => $user->profile->live_trading_balance - $data['amount']
            ]);

            return $copyTrade;
        });

        // Dispatch the event with the copyTrade data
        event(new CopyTradeStarted($user, $data, $execution, $masterTrader));

        return $execution;
    }

    /**
     * Process payout for a matured investment cycle
     *
     * @param InvestmentHistory $investment
     * @return bool
     * @throws Throwable
     */
    public function processCyclePayout(InvestmentHistory $investment): bool
    {
        return DB::transaction(function () use ($investment) {

            // Verify investment is running
            if ($investment->status !== 'running') {
                return false;
            }

            // Verify the cycle has matured
            $now = Carbon::now();
            $nextTime = Carbon::parse($investment->next_time);

            if ($now->lt($nextTime)) {
                return false;
            }

            // Get user
            $user = $investment->user;
            if (!$user) {
                throw new Exception("User not found for investment ID: $investment->id");
            }

            // Increment cycle count
            $newCycleCount = $investment->repeat_time_count + 1;

            // Check if this is the final cycle
            $isFinalCycle = $newCycleCount >= $investment->repeat_time;

            // Calculate payout amount
            $payoutAmount = $investment->interest; // Interest per cycle

            // If final cycle and capital back is enabled, add the principal
            if ($isFinalCycle && $investment->capital_back_status === 'yes') {
                $payoutAmount += $investment->amount;
            }

            // Credit the payout to user's live trading balance
            $user->profile()->increment('live_trading_balance', $payoutAmount);

            // Update investment record
            $updateData = [
                'repeat_time_count' => $newCycleCount,
                'last_time' => $now->toDateTimeString(),
            ];

            if ($isFinalCycle) {
                // Mark as completed
                $updateData['status'] = 'completed';
                $updateData['next_time'] = $now->toDateTimeString();
            } else {
                // Schedule next cycle
                $updateData['next_time'] = $now->copy()->addHours($investment->period)->toDateTimeString();
            }

            $investment->update($updateData);

            // payout data
            $payoutData = [
                'cycle' => $newCycleCount,
                'total_cycles' => $investment->repeat_time,
                'payout_amount' => $payoutAmount,
                'interest' => $investment->interest,
                'capital_returned' => $isFinalCycle && $investment->capital_back_status === 'yes',
                'is_final_cycle' => $isFinalCycle,
            ];

            // Dispatch payout event
            event(new InvestmentPayout($user, $investment, $payoutData));

            return true;
        });
    }

    /**
     * Process all matured investment cycles - called by a scheduled task (cron job)
     *
     * @return array
     */
    public function processAllMaturedCycles(): array
    {
        $now = Carbon::now();

        // Get all running investments where next_time has passed
        $maturedInvestments = InvestmentHistory::where('status', 'running')
            ->where('next_time', '<=', $now->toDateTimeString())
            ->with('user.profile')
            ->get();

        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($maturedInvestments as $investment) {
            try {
                $success = $this->processCyclePayout($investment);
                if ($success) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                $errors[] = [
                    'investment_id' => $investment->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'total_matured' => $maturedInvestments->count(),
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
            'timestamp' => $now->toDateTimeString()
        ];
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
