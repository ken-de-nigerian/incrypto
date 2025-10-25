<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Auth;
use Inertia\Inertia;

class ManageUserTransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return Inertia::render('User/Transaction', [
            'crypto_swaps' => $this->getCryptoSwaps($user),
            'received_cryptos' => $this->getReceivedCryptos($user),
            'sent_cryptos' => $this->getSentCryptos($user)
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
            'wallet_address' => $crypto->wallet_address,
            'amount' => $crypto->amount,
            'transaction_hash' => $crypto->transaction_hash,
            'fee' => $crypto->fee,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
    }
}
