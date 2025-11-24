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
use Illuminate\Validation\ValidationException;
use Throwable;

class TradeService
{
    /**
     * Execute a new trade for the user
     *
     * @throws Exception
     * @throws Throwable
     */
    public function executeTrade(User $user, array $data)
    {
        $expiryTime = $this->calculateExpiryTime($data['duration']);
        $shouldWin = $data['trading_mode'] === 'demo';

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

                $isMasterTrader = $user->isMasterTrader();
                $masterTrader = $user->masterTrader()->lockForUpdate()->first();

                if ($isMasterTrader) {
                    $masterTrader->increment('total_trades');

                    $activeCopies = CopyTrade::where('master_trader_id', $masterTrader->id)
                        ->where('status', 'active')
                        ->with(['user.profile'])
                        ->get();

                    // Prepare bulk operations
                    $copierTrades = [];
                    $copyTransactions = [];
                    $balanceUpdates = [];
                    $eventsToDispatch = [];

                    foreach ($activeCopies as $copyTrade) {
                        $copierUser = $copyTrade->user;
                        $copyAmount = $data['amount'] * $copyTrade->multiplier;
                        $copierBalance = $copierUser->profile->live_trading_balance;

                        if ($copierBalance >= $copyAmount) {
                            // Store balance update for bulk processing
                            $balanceUpdates[] = [
                                'user_id' => $copierUser->id,
                                'amount' => $copyAmount
                            ];

                            // Prepare copier trade data
                            $copierTradeData = [
                                'user_id' => $copierUser->id,
                                'category' => $data['category'],
                                'pair' => $data['pair'],
                                'pair_name' => $data['pair_name'],
                                'type' => $data['type'],
                                'amount' => $copyAmount,
                                'leverage' => $data['leverage'],
                                'duration' => $data['duration'],
                                'entry_price' => $data['entry_price'],
                                'trading_mode' => 'live',
                                'is_demo_forced_win' => false,
                                'status' => 'Open',
                                'pnl' => 0,
                                'expiry_time' => $expiryTime,
                                'opened_at' => now(),
                            ];
                            $copierTrades[] = $copierTradeData;

                            $transactionType = match(strtolower($data['type'])) {
                                'buy', 'long', 'up', 'call' => 'up',
                                default => 'down',
                            };

                            // Prepare transaction data
                            $copyTransactions[] = [
                                'copy_trade_id' => $copyTrade->id,
                                'copier_user' => $copierUser,
                                'type' => $transactionType,
                                'amount' => $copyAmount,
                                'description' => "Copied trade on {$data['pair_name']} (×$copyTrade->multiplier)",
                                'metadata' => array_merge($trade->toArray(), [
                                    'multiplier' => $copyTrade->multiplier,
                                    'master_amount' => $data['amount'],
                                ]),
                            ];
                        }
                    }

                    // Update balances instead of individual decrements
                    if (!empty($balanceUpdates)) {
                        foreach ($balanceUpdates as $update) {
                            DB::table('user_profiles')
                                ->where('user_id', $update['user_id'])
                                ->decrement('live_trading_balance', $update['amount']);
                        }
                    }

                    // Insert copier trades
                    if (!empty($copierTrades)) {
                        Trade::insert($copierTrades);

                        $insertedTrades = Trade::where('entry_price', $data['entry_price'])
                            ->where('opened_at', now())
                            ->whereIn('user_id', array_column($balanceUpdates, 'user_id'))
                            ->get()
                            ->keyBy('user_id');
                    }

                    // Insert copy trade transactions with proper copier_trade_id
                    if (!empty($copyTransactions)) {
                        $transactionsToInsert = [];
                        foreach ($copyTransactions as $transactionData) {
                            $copierUserId = $transactionData['copier_user']->id;
                            $copierTradeId = $insertedTrades[$copierUserId]->id ?? null;

                            $metadata = $transactionData['metadata'];
                            $metadata['copier_trade_id'] = $copierTradeId;

                            $transactionsToInsert[] = [
                                'copy_trade_id' => $transactionData['copy_trade_id'],
                                'type' => $transactionData['type'],
                                'amount' => $transactionData['amount'],
                                'description' => $transactionData['description'],
                                'metadata' => json_encode($metadata),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            // Store for event dispatching
                            $eventsToDispatch[] = [
                                'user' => $transactionData['copier_user'],
                                'transaction_id' => null,
                            ];
                        }

                        CopyTradeTransaction::insert($transactionsToInsert);

                        // Get inserted transactions for events
                        $insertedTransactions = CopyTradeTransaction::whereIn('copy_trade_id', array_column($transactionsToInsert, 'copy_trade_id'))
                            ->where('created_at', now())
                            ->get();

                        // Dispatch events
                        foreach ($insertedTransactions as $idx => $transaction) {
                            if (isset($eventsToDispatch[$idx])) {
                                event(new CopyTradeExecuted($eventsToDispatch[$idx]['user'], $transaction));
                            }
                        }
                    }
                }
            } else {
                $profile->decrement('demo_trading_balance', $data['amount']);
            }

            return $trade;
        });

        event(new TradeExecuted($user, $data, $expiryTime));

        return $execution;
    }

    /**
     * Close a trade
     *
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

            if ($trade->trading_mode === 'live') {
                $isMasterTrader = $user->isMasterTrader();
                $masterTrader = $user->masterTrader()->lockForUpdate()->first();

                if ($isMasterTrader) {
                    // Update profit/loss
                    if ($data['pnl'] >= 0) {
                        $masterTrader->increment('total_profit', $data['pnl']);
                    } else {
                        $masterTrader->increment('total_loss', abs($data['pnl']));
                    }

                    $tradeStats = Trade::where('user_id', $user->id)
                        ->where('trading_mode', 'live')
                        ->where('status', 'Closed')
                        ->selectRaw('
                        COUNT(*) as total_trades,
                        SUM(CASE WHEN pnl >= 0 THEN 1 ELSE 0 END) as winning_trades,
                        SUM(pnl) as total_pnl,
                        SUM(amount) as total_invested
                    ')->first();

                    $totalTrades = $tradeStats->total_trades;
                    $winningTrades = $tradeStats->winning_trades;
                    $losingTrades = $totalTrades - $winningTrades;

                    $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;

                    $totalPnl = $tradeStats->total_pnl;
                    $totalInvested = $tradeStats->total_invested;
                    $gainPercentage = $totalInvested > 0 ? ($totalPnl / $totalInvested) * 100 : 0;

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

                $copyTransactions = CopyTradeTransaction::where('metadata->id', $trade->id)
                    ->with(['copyTrade.user.profile'])
                    ->get();

                // Prepare bulk updates
                $balanceUpdates = [];
                $copyTradeStatUpdates = [];
                $copierTradeUpdates = [];
                $transactionUpdates = [];
                $eventsToDispatch = [];

                foreach ($copyTransactions as $transaction) {
                    $copyTrade = $transaction->copyTrade;
                    $copierUser = $copyTrade->user;

                    if (!$copyTrade || !$copierUser) continue;

                    $multiplier = $transaction->metadata['multiplier'] ?? 1;
                    $copierPnl = (float)$data['pnl'] * (float)$multiplier;

                    // Prepare copier trade update
                    $copierTradeId = $transaction->metadata['copier_trade_id'] ?? null;
                    if ($copierTradeId) {
                        $copierTradeUpdates[] = [
                            'id' => $copierTradeId,
                            'exit_price' => $data['exit_price'],
                            'pnl' => $copierPnl,
                            'status' => 'Closed',
                            'closed_at' => $data['closed_at'],
                            'is_auto_close' => $isAutoClose
                        ];
                    }

                    // Prepare balance update
                    $balanceUpdates[] = [
                        'user_id' => $copierUser->id,
                        'amount' => $transaction->amount + $copierPnl
                    ];

                    // Prepare copy trade stats update
                    $copyTradeStatUpdates[] = [
                        'id' => $copyTrade->id,
                        'field' => $copierPnl >= 0 ? 'current_profit' : 'current_loss',
                        'amount' => abs($copierPnl)
                    ];

                    // Prepare transaction metadata update
                    $metadata = $transaction->metadata ?? [];
                    $metadata['exit_price'] = $data['exit_price'];
                    $metadata['pnl'] = $copierPnl;
                    $metadata['master_pnl'] = $data['pnl'];
                    $metadata['status'] = 'Closed';
                    $metadata['closed_at'] = $data['closed_at'];

                    $transactionUpdates[] = [
                        'id' => $transaction->id,
                        'metadata' => $metadata
                    ];

                    $eventsToDispatch[] = [
                        'user' => $copierUser,
                        'transaction' => $transaction
                    ];
                }

                // Update copier trades
                if (!empty($copierTradeUpdates)) {
                    foreach ($copierTradeUpdates as $update) {
                        Trade::where('id', $update['id'])
                            ->where('status', 'Open')
                            ->update([
                                'exit_price' => $update['exit_price'],
                                'pnl' => $update['pnl'],
                                'status' => $update['status'],
                                'closed_at' => $update['closed_at'],
                                'is_auto_close' => $update['is_auto_close'],
                                'updated_at' => now()
                            ]);
                    }
                }

                // Update balances
                if (!empty($balanceUpdates)) {
                    foreach ($balanceUpdates as $update) {
                        DB::table('user_profiles')
                            ->where('user_id', $update['user_id'])
                            ->increment('live_trading_balance', $update['amount']);
                    }
                }

                // Update copy trade stats
                if (!empty($copyTradeStatUpdates)) {
                    foreach ($copyTradeStatUpdates as $update) {
                        CopyTrade::where('id', $update['id'])
                            ->increment($update['field'], $update['amount']);
                    }
                }

                // Update transaction metadata
                if (!empty($transactionUpdates)) {
                    foreach ($transactionUpdates as $update) {
                        CopyTradeTransaction::where('id', $update['id'])
                            ->update([
                                'metadata' => $update['metadata'],
                                'updated_at' => now()
                            ]);
                    }
                }

                // Dispatch events
                foreach ($eventsToDispatch as $eventData) {
                    event(new CopyTradeClosed($eventData['user'], $eventData['transaction']));
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
     * Cancel an investment
     *
     * @throws Throwable
     */
    public function cancelInvestment(User $user, array $data, InvestmentHistory $investment)
    {
        $payoutOption = $data['payout_option'];

        return DB::transaction(function () use ($user, $payoutOption, $investment, $data) {

            // Check if investment is already canceled or completed
            if ($investment->status == 'cancelled') {
                throw ValidationException::withMessages([
                    'message' => 'This investment has already been cancelled',
                ]);
            }

            // Validate payout for no payout option
            if ($payoutOption === 'nothing' && $investment->repeat_time_count > 0) {
                throw ValidationException::withMessages([
                    'payout_option' => 'No payout option is not available for investments with completed cycles.',
                ]);
            }

            // Calculate payout amount based on selected option
            $payoutAmount = $this->calculatePayoutAmount($investment, $payoutOption);

            // Cancel the investment
            $investment->status = 'cancelled';
            $investment->save();

            // Update user balance if there's a payout
            if ($payoutAmount > 0) {
                $user->profile()->increment('live_trading_balance', $payoutAmount);
            }

            // payout data
            $payoutData = [
                'cycle' => $investment->repeat_time_count,
                'total_cycles' => $investment->repeat_time,
                'payout_amount' => $payoutAmount,
                'interest' => $investment->interest,
                'capital_returned' => $investment->capital_back_status === 'yes',
                'is_final_cycle' => true,
            ];

            // Dispatch payout event
            event(new InvestmentPayout($user, $investment, $payoutData));

            return true;
        });
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
            if ($data['commission_fee'] > $liveBalance) {
                throw new Exception('Insufficient balance required to copy trader.');
            }

            // Create copy trade
            $copyTrade = $user->copyTrades()->create([
                'master_trader_id' => $masterTrader->id,
                'multiplier' => $data['multiplier'],
                'total_commission_paid' => $data['commission_fee'],
                'status' => 'active',
                'started_at' => now(),
            ]);

            // Deduct amount from live trading balance
            $user->profile->update([
                'live_trading_balance' => $user->profile->live_trading_balance - $data['commission_fee']
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

    /**
     * Calculate payout amount based on selected option
     *
     * @param InvestmentHistory $investment
     * @param string $payoutOption
     * @return float
     */
    protected function calculatePayoutAmount(InvestmentHistory $investment, string $payoutOption): float
    {
        // Calculate interest per cycle
        $interestPerCycle = $investment->interest ?? 0;

        // Calculate paid interest (interest already paid out)
        $paidInterest = $interestPerCycle * ($investment->repeat_time_count ?? 0);

        // Calculate unpaid interest (interest for remaining cycles)
        $remainingCycles = ($investment->repeat_time ?? 0) - ($investment->repeat_time_count ?? 0);
        $unpaidInterest = $interestPerCycle * $remainingCycles;

        // Total interest (paid and unpaid)
        $interestOnlyTotal = $paidInterest + $unpaidInterest;

        // Total ROI = capital + total interest earned
        $totalROI = $investment->amount + $interestOnlyTotal;

        return match ($payoutOption) {
            'capital_and_interest' => $investment->amount + $unpaidInterest,
            'capital_only' => max(0, $totalROI - $interestOnlyTotal),
            'interest_only' => $interestOnlyTotal,
            'nothing' => 0.0,
            default => 0.0,
        };
    }
}
