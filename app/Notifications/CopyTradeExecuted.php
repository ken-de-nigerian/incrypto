<?php

namespace App\Notifications;

use App\Models\CopyTradeTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CopyTradeExecuted extends Notification
{
    use Queueable;

    public CopyTradeTransaction $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(CopyTradeTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Access the trade details stored in metadata
        $metadata = $this->transaction->metadata ?? [];

        $pairName = $metadata['pair_name'] ?? $metadata['pair'] ?? 'Unknown Asset';
        $type = ucfirst($this->transaction->type);
        $expiryTime = $metadata['expiry_time'] ?? 'N/A';
        $amount = number_format($this->transaction->amount, 2);

        return (new MailMessage)
            ->subject("Copy Trade Executed: $pairName ($type)")
            ->view('emails.copy_trade_executed', [
                'user' => $notifiable,
                'transaction' => $this->transaction,
                'metadata' => $metadata,
                'pairName' => $pairName,
                'amount' => $amount,
                'expiryTime' => $expiryTime
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Access the trade details stored in metadata
        $metadata = $this->transaction->metadata ?? [];

        $pairName = $metadata['pair_name'] ?? $metadata['pair'] ?? 'Unknown Asset';
        $amount = number_format($this->transaction->amount, 2);
        $type = ucfirst($this->transaction->type);

        return [
            'type' => 'copy_trade_executed',
            'title' => 'Copy Trade Executed',
            'content' => "A $type copy trade for $pairName with an amount of \$$amount has been opened.",
            'trade_details' => [
                'transaction_id' => $this->transaction->id,
                'pair' => $pairName,
                'amount' => $this->transaction->amount,
                'type' => $this->transaction->type,
                'entry_price' => $metadata['entry_price'] ?? null,
                'duration' => $metadata['duration'] ?? null,
                'leverage' => $metadata['leverage'] ?? 1,
                'expiry_time' => $metadata['expiry_time'] ?? null,
                'trading_mode' => 'live',
                'opened_at' => $this->transaction->created_at,
            ]
        ];
    }
}
