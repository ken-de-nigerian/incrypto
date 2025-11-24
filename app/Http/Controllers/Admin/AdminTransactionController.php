<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelInvestmentRequest;
use App\Http\Requests\CloseTradeRequest;
use App\Models\CryptoSwap;
use App\Models\InvestmentHistory;
use App\Models\ReceivedCrypto;
use App\Models\SendCrypto;
use App\Models\Trade;
use App\Services\ApproveReceivedCryptoService;
use App\Services\ApproveSentCryptoService;
use App\Services\RejectReceivedCryptoService;
use App\Services\RejectSentCryptoService;
use App\Services\TradeService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Random\RandomException;
use Throwable;

class AdminTransactionController extends Controller
{
    protected ApproveReceivedCryptoService $approveReceivedCryptoService;
    protected ApproveSentCryptoService $approveSentCryptoService;
    protected RejectReceivedCryptoService $rejectReceivedCryptoService;
    protected RejectSentCryptoService $rejectSentCryptoService;

    public function __construct(
        ApproveReceivedCryptoService $approveReceivedCryptoService,
        ApproveSentCryptoService $approveSentCryptoService,
        RejectReceivedCryptoService $rejectReceivedCryptoService,
        RejectSentCryptoService $rejectSentCryptoService
    ) {
        $this->approveReceivedCryptoService = $approveReceivedCryptoService;
        $this->approveSentCryptoService = $approveSentCryptoService;
        $this->rejectReceivedCryptoService = $rejectReceivedCryptoService;
        $this->rejectSentCryptoService = $rejectSentCryptoService;
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');

        return Inertia::render('Admin/Transaction', [
            'crypto_swaps' => $this->getCryptoSwaps(),
            'received_cryptos' => $this->getReceivedCryptos(),
            'sent_cryptos' => $this->getSentCryptos(),
            'forex_trades' => $this->getForexTrades(),
            'stock_trades' => $this->getStockTrades(),
            'crypto_trades' => $this->getCryptoTrades(),
            'investment_histories' => $this->getInvestmentHistories(),
            'tab' => $tab,
        ]);
    }

    protected function getCryptoSwaps()
    {
        $cryptos = CryptoSwap::whereHas('user')
            ->with('user.profile')
            ->select([
                'id',
                'user_id',
                'from_token',
                'to_token',
                'from_amount',
                'to_amount',
                'transaction_hash',
                'chain',
                'status',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->get();

        return $cryptos->map(fn ($crypto) => [
            'id' => $crypto->id,
            'user_id' => $crypto->user_id,
            'user_name' => ($crypto->user?->first_name ?? 'Unknown') . ' ' . ($crypto->user?->last_name ?? 'User'),
            'user_email' => $crypto->user?->email ?? 'N/A',
            'from_token' => $crypto->from_token,
            'to_token' => $crypto->to_token,
            'from_amount' => $crypto->from_amount,
            'to_amount' => $crypto->to_amount,
            'transaction_hash' => $crypto->transaction_hash,
            'chain' => $crypto->chain,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
    }

    protected function getReceivedCryptos()
    {
        $cryptos = ReceivedCrypto::whereHas('user')
            ->with('user.profile')
            ->select([
                'id',
                'user_id',
                'token_symbol',
                'wallet_address',
                'amount',
                'transaction_hash',
                'status',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->get();

        return $cryptos->map(fn ($crypto) => [
            'id' => $crypto->id,
            'user_id' => $crypto->user_id,
            'user_name' => ($crypto->user?->first_name ?? 'Unknown') . ' ' . ($crypto->user?->last_name ?? 'User'),
            'user_email' => $crypto->user?->email ?? 'N/A',
            'token_symbol' => $crypto->token_symbol,
            'wallet_address' => $crypto->wallet_address,
            'amount' => $crypto->amount,
            'transaction_hash' => $crypto->transaction_hash,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
    }

    protected function getSentCryptos()
    {
        $cryptos = SendCrypto::whereHas('user')
            ->with('user.profile')
            ->select([
                'id',
                'user_id',
                'token_symbol',
                'recipient_address',
                'amount',
                'transaction_hash',
                'fee',
                'status',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->get();

        return $cryptos->map(fn ($crypto) => [
            'id' => $crypto->id,
            'user_id' => $crypto->user_id,
            'user_name' => ($crypto->user?->first_name ?? 'Unknown') . ' ' . ($crypto->user?->last_name ?? 'User'),
            'user_email' => $crypto->user?->email ?? 'N/A',
            'token_symbol' => $crypto->token_symbol,
            'recipient_address' => $crypto->recipient_address,
            'amount' => $crypto->amount,
            'transaction_hash' => $crypto->transaction_hash,
            'fee' => $crypto->fee,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
    }

    protected function getForexTrades()
    {
        return Trade::whereHas('user')
            ->with('user')
            ->where('category', 'forex')
            ->select([
                'id',
                'user_id',
                'pair',
                'pair_name',
                'type',
                'amount',
                'leverage',
                'duration',
                'entry_price',
                'exit_price',
                'status',
                'pnl',
                'trading_mode',
                'opened_at',
                'closed_at',
                'expiry_time',
                'created_at',
            ])
            ->latest()
            ->get()
            ->map(fn ($trade) => [
                'id' => $trade->id,
                'user_id' => $trade->user_id,
                'user_name' => ($trade->user?->first_name ?? 'Unknown') . ' ' . ($trade->user?->last_name ?? 'User'),
                'user_email' => $trade->user?->email ?? 'N/A',
                'pair' => $trade->pair,
                'pair_name' => $trade->pair_name,
                'type' => $trade->type,
                'amount' => $trade->amount,
                'leverage' => $trade->leverage,
                'duration' => $trade->duration,
                'entry_price' => $trade->entry_price,
                'exit_price' => $trade->exit_price,
                'status' => $trade->status,
                'pnl' => $trade->pnl,
                'trading_mode' => $trade->trading_mode,
                'opened_at' => $trade->opened_at,
                'closed_at' => $trade->closed_at,
                'expiry_time' => $trade->expiry_time,
                'created_at' => $trade->created_at,
            ])
            ->toArray();
    }

    protected function getStockTrades()
    {
        return Trade::whereHas('user')
            ->with('user')
            ->where('category', 'stock')
            ->select([
                'id',
                'user_id',
                'pair',
                'pair_name',
                'type',
                'amount',
                'leverage',
                'duration',
                'entry_price',
                'exit_price',
                'status',
                'pnl',
                'trading_mode',
                'opened_at',
                'closed_at',
                'expiry_time',
                'created_at',
            ])
            ->latest()
            ->get()
            ->map(fn ($trade) => [
                'id' => $trade->id,
                'user_id' => $trade->user_id,
                'user_name' => ($trade->user?->first_name ?? 'Unknown') . ' ' . ($trade->user?->last_name ?? 'User'),
                'user_email' => $trade->user?->email ?? 'N/A',
                'pair' => $trade->pair,
                'pair_name' => $trade->pair_name,
                'type' => $trade->type,
                'amount' => $trade->amount,
                'leverage' => $trade->leverage,
                'duration' => $trade->duration,
                'entry_price' => $trade->entry_price,
                'exit_price' => $trade->exit_price,
                'status' => $trade->status,
                'pnl' => $trade->pnl,
                'trading_mode' => $trade->trading_mode,
                'opened_at' => $trade->opened_at,
                'closed_at' => $trade->closed_at,
                'expiry_time' => $trade->expiry_time,
                'created_at' => $trade->created_at,
            ])
            ->toArray();
    }

    protected function getCryptoTrades()
    {
        return Trade::whereHas('user')
            ->with('user')
            ->where('category', 'crypto')
            ->select([
                'id',
                'user_id',
                'pair',
                'pair_name',
                'type',
                'amount',
                'leverage',
                'duration',
                'entry_price',
                'exit_price',
                'status',
                'pnl',
                'trading_mode',
                'opened_at',
                'closed_at',
                'expiry_time',
                'created_at',
            ])
            ->latest()
            ->get()
            ->map(fn ($trade) => [
                'id' => $trade->id,
                'user_id' => $trade->user_id,
                'user_name' => ($trade->user?->first_name ?? 'Unknown') . ' ' . ($trade->user?->last_name ?? 'User'),
                'user_email' => $trade->user?->email ?? 'N/A',
                'pair' => $trade->pair,
                'pair_name' => $trade->pair_name,
                'type' => $trade->type,
                'amount' => $trade->amount,
                'leverage' => $trade->leverage,
                'duration' => $trade->duration,
                'entry_price' => $trade->entry_price,
                'exit_price' => $trade->exit_price,
                'status' => $trade->status,
                'pnl' => $trade->pnl,
                'trading_mode' => $trade->trading_mode,
                'opened_at' => $trade->opened_at,
                'closed_at' => $trade->closed_at,
                'expiry_time' => $trade->expiry_time,
                'created_at' => $trade->created_at,
            ])
            ->toArray();
    }

    protected function getInvestmentHistories()
    {
        return InvestmentHistory::whereHas('user')
            ->with(['user', 'plan'])
            ->select([
                'id',
                'user_id',
                'plan_id',
                'amount',
                'interest',
                'period',
                'repeat_time',
                'repeat_time_count',
                'next_time',
                'last_time',
                'status',
                'capital_back_status',
                'created_at',
            ])
            ->latest()
            ->get()
            ->map(fn ($history) => [
                'id' => $history->id,
                'user_id' => $history->user_id,
                'user_name' => ($history->user?->first_name ?? 'Unknown') . ' ' . ($history->user?->last_name ?? 'User'),
                'user_email' => $history->user?->email ?? 'N/A',
                'plan_id' => $history->plan_id,
                'plan_name' => $history->plan->name ?? 'N/A',
                'amount' => $history->amount,
                'interest' => $history->interest,
                'period' => $history->period,
                'repeat_time' => $history->repeat_time,
                'repeat_time_count' => $history->repeat_time_count,
                'next_time' => $history->next_time,
                'last_time' => $history->last_time,
                'status' => $history->status,
                'capital_back_status' => $history->capital_back_status,
                'created_at' => $history->created_at,
            ])
            ->toArray();
    }

    /**
     * @throws Throwable
     */
    public function approve()
    {
        $transactionId = request('transaction_id');
        $transactionType = request('transaction_type');
        $amount = request('amount');

        try {
            match($transactionType) {
                'received' => $this->approveReceivedCrypto($transactionId, $amount),
                'sent' => $this->approveSentCrypto($transactionId),
                default => throw new Exception('Invalid transaction type')
            };

            return $this->notify('success', 'Transaction approved successfully.')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', $e->getMessage())->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function reject()
    {
        $transactionId = request('transaction_id');
        $transactionType = request('transaction_type');

        try {
            match($transactionType) {
                'received' => $this->rejectReceivedCrypto($transactionId),
                'sent' => $this->rejectSentCrypto($transactionId),
                default => throw new Exception('Invalid transaction type')
            };

            return $this->notify('success', 'Transaction rejected successfully.')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', $e->getMessage())->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function closeTrade(CloseTradeRequest $request, Trade $trade, TradeService $tradeService)
    {
        try {
            $result = $tradeService->closeTrade(
                $trade->user,
                $request->validated(),
                $trade
            );

            if (!$result) {
                return $this->notify('error', 'Failed to close trade - invalid state')->toBack();
            }

            return $this->notify('success', 'Trade closed successfully')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function cancelInvestment(CancelInvestmentRequest $request, InvestmentHistory $investment, TradeService $tradeService)
    {
        try {
            $result = $tradeService->cancelInvestment(
                $investment->user,
                $request->validated(),
                $investment
            );

            if (!$result) {
                return $this->notify('error', 'Failed to cancel investment - invalid state')->toBack();
            }

            return $this->notify('success', 'Investment cancelled successfully')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     * @throws RandomException
     */
    private function approveReceivedCrypto($id, $amount)
    {
        $this->approveReceivedCryptoService->approve($id, $amount);
    }

    /**
     * @throws Throwable
     */
    private function rejectReceivedCrypto($id)
    {
        $this->rejectReceivedCryptoService->reject($id);
    }

    /**
     * @throws Throwable
     * @throws RandomException
     */
    private function approveSentCrypto($id)
    {
        $this->approveSentCryptoService->approve($id);
    }

    /**
     * @throws Throwable
     */
    private function rejectSentCrypto($id)
    {
        $this->rejectSentCryptoService->reject($id);
    }
}
