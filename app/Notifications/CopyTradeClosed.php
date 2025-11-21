<?php

namespace App\Notifications;

use App\Models\CopyTradeTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CopyTradeClosed extends Notification
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
        $metadata = $this->transaction->metadata ?? [];
        $pairName = $metadata['pair_name'] ?? $metadata['pair'] ?? 'Unknown Asset';

        // PnL stored in metadata from TradeService logic
        $pnl = isset($metadata['pnl']) ? (float)$metadata['pnl'] : 0;
        $pnlFormatted = ($pnl >= 0 ? '+' : '-') . '$' . number_format(abs($pnl), 2);
        $status = $pnl >= 0 ? 'Profit' : 'Loss';

        return (new MailMessage)
            ->subject("Copy Trade Closed: $pairName ($status)")
            ->view('emails.copy_trade_closed', [
                'user' => $notifiable,
                'transaction' => $this->transaction,
                'metadata' => $metadata,
                'pairName' => $pairName,
                'pnl' => $pnl,
                'pnlFormatted' => $pnlFormatted,
                'exitPrice' => number_format($metadata['exit_price'] ?? 0, 5),
                'closedAt' => $metadata['closed_at'] ?? now()->toDateTimeString(),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $metadata = $this->transaction->metadata ?? [];
        $pairName = $metadata['pair_name'] ?? $metadata['pair'] ?? 'Unknown Asset';
        $pnl = isset($metadata['pnl']) ? (float)$metadata['pnl'] : 0;

        return [
            'type' => 'copy_trade_closed',
            'title' => 'Copy Trade Closed',
            'content' => "Your copy trade on $pairName has closed with a PnL of " . number_format($pnl, 2),
            'trade_details' => [
                'transaction_id' => $this->transaction->id,
                'pair' => $pairName,
                'amount' => $this->transaction->amount,
                'type' => $this->transaction->type,
                'exit_price' => $metadata['exit_price'] ?? null,
                'pnl' => $pnl,
                'status' => 'Closed',
                'closed_at' => $metadata['closed_at'] ?? null,
            ]
        ];
    }
}
