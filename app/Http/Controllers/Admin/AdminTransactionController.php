<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoSwap;
use App\Models\ReceivedCrypto;
use App\Models\SendCrypto;
use Inertia\Inertia;

class AdminTransactionController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Transaction', [
            'crypto_swaps' => $this->getCryptoSwaps(),
            'received_cryptos' => $this->getReceivedCryptos(),
            'sent_cryptos' => $this->getSentCryptos()
        ]);
    }

    protected function getCryptoSwaps()
    {
        $cryptos = CryptoSwap::with('user')
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
            ->limit(50)
            ->get();

        return $cryptos->map(fn ($crypto) => [
            'id' => $crypto->id,
            'user_id' => $crypto->user_id,
            'user_name' => $crypto->user?->first_name . ' ' .$crypto->user?->last_name ?? 'Unknown User',
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
        $cryptos = ReceivedCrypto::with('user')
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
            ->limit(50)
            ->get();

        return $cryptos->map(fn ($crypto) => [
            'id' => $crypto->id,
            'user_id' => $crypto->user_id,
            'user_name' => $crypto->user?->first_name . ' ' .$crypto->user?->last_name ?? 'Unknown User',
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
        $cryptos = SendCrypto::with('user')
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
            ->limit(50)
            ->get();

        return $cryptos->map(fn ($crypto) => [
            'id' => $crypto->id,
            'user_id' => $crypto->user_id,
            'user_name' => $crypto->user?->first_name . ' ' .$crypto->user?->last_name ?? 'Unknown User',
            'user_email' => $crypto->user?->email ?? 'N/A',
            'token_symbol' => $crypto->token_symbol,
            'wallet_address' => $crypto->recipient_address,
            'amount' => $crypto->amount,
            'transaction_hash' => $crypto->transaction_hash,
            'fee' => $crypto->fee,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
    }

    public function approve()
    {
        $transactionId = request('transaction_id');
        $transactionType = request('transaction_type');
        $amount = request('amount');

        try {
            match($transactionType) {
                'swap' => $this->approveCryptoSwap($transactionId),
                'received' => $this->approveReceivedCrypto($transactionId, $amount),
                'sent' => $this->approveSentCrypto($transactionId),
                default => throw new \Exception('Invalid transaction type')
            };

            return back()->with('success', 'Transaction approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject()
    {
        $transactionId = request('transaction_id');
        $transactionType = request('transaction_type');

        try {
            match($transactionType) {
                'swap' => $this->rejectCryptoSwap($transactionId),
                'received' => $this->rejectReceivedCrypto($transactionId),
                'sent' => $this->rejectSentCrypto($transactionId),
                default => throw new \Exception('Invalid transaction type')
            };

            return back()->with('success', 'Transaction rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function approveCryptoSwap($id)
    {
        $swap = CryptoSwap::findOrFail($id);
        $swap->update(['status' => 'completed']);
    }

    private function approveReceivedCrypto($id, $amount)
    {
        $crypto = ReceivedCrypto::findOrFail($id);
        $crypto->update([
            'amount' => $amount,
            'status' => 'completed'
        ]);
    }

    private function approveSentCrypto($id)
    {
        $crypto = SendCrypto::findOrFail($id);
        $crypto->update(['status' => 'completed']);
    }

    private function rejectCryptoSwap($id)
    {
        $swap = CryptoSwap::findOrFail($id);
        $swap->update(['status' => 'failed']);
    }

    private function rejectReceivedCrypto($id)
    {
        $crypto = ReceivedCrypto::findOrFail($id);
        $crypto->update(['status' => 'failed']);
    }

    private function rejectSentCrypto($id)
    {
        $crypto = SendCrypto::findOrFail($id);
        $crypto->update(['status' => 'failed']);
    }
}
