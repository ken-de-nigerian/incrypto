<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoSwap;
use App\Models\ReceivedCrypto;
use App\Models\SendCrypto;
use App\Services\ApproveReceivedCryptoService;
use App\Services\ApproveSentCryptoService;
use App\Services\RejectReceivedCryptoService;
use App\Services\RejectSentCryptoService;
use Exception;
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
            'wallet_address' => $crypto->recipient_address,
            'amount' => $crypto->amount,
            'transaction_hash' => $crypto->transaction_hash,
            'fee' => $crypto->fee,
            'status' => $crypto->status,
            'created_at' => $crypto->created_at,
        ])->toArray();
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
