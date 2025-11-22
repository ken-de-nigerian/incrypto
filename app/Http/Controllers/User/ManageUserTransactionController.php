<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ManageUserTransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'all');

        return Inertia::render('User/Transaction', [
            'crypto_swaps' => $this->getCryptoSwaps($user),
            'received_cryptos' => $this->getReceivedCryptos($user),
            'sent_cryptos' => $this->getSentCryptos($user),
            'forex_trades' => $this->getForexTrades($user),
            'stock_trades' => $this->getStockTrades($user),
            'crypto_trades' => $this->getCryptoTrades($user),
            'investment_histories' => $this->getInvestmentHistories($user),
            'tab' => $tab,
        ]);
    }

    protected function getCryptoSwaps($user)
    {
        $cryptos = $user->cryptoSwaps()
            ->select([
                'id',
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

    protected function getReceivedCryptos($user)
    {
        $cryptos = $user->receivedCryptos()
            ->select([
                'id',
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
            'token_symbol' => $crypto->token_symbol,
            'wallet_address' => $crypto->wallet_address,
            'amount' => $crypto->amount,
            'transaction_hash' => $crypto->transaction_hash,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
    }

    protected function getSentCryptos($user)
    {
        $cryptos = $user->sentCryptos()
            ->select([
                'id',
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
            'token_symbol' => $crypto->token_symbol,
            'recipient_address' => $crypto->recipient_address,
            'amount' => $crypto->amount,
            'transaction_hash' => $crypto->transaction_hash,
            'fee' => $crypto->fee,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
    }

    protected function getForexTrades($user)
    {
        return $user->trades()
            ->where('category', 'forex')
            ->select([
                'id',
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
            ->toArray();
    }

    protected function getStockTrades($user)
    {
        return $user->trades()
            ->where('category', 'stock')
            ->select([
                'id',
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
            ->toArray();
    }

    protected function getCryptoTrades($user)
    {
        return $user->trades()
            ->where('category', 'crypto')
            ->select([
                'id',
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
            ->toArray();
    }

    protected function getInvestmentHistories($user)
    {
        return $user->investmentHistories()
            ->with('plan')
            ->select([
                'id',
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
}
