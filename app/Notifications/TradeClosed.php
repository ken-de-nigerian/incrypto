<?php

namespace App\Notifications;

use App\Models\Trade;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TradeClosed extends Notification
{
    use Queueable;

    public Trade $trade;

    /**
     * Create a new notification instance.
     */
    public function __construct(Trade $trade)
    {
        $this->trade = $trade;
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
        $pnl_sign = $this->trade->pnl >= 0 ? '+' : '';
        $pnl_formatted = number_format($this->trade->pnl, 2);

        $subject = 'Trade Closed: ' . $this->trade->pair . ' Result: ' . $pnl_sign . '$' . $pnl_formatted;

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.trade_closed', [
                'user' => $notifiable,
                'trade' => $this->trade
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $pairName = $this->trade->pair;
        $type = ucfirst($this->trade->type);
        $pnl = number_format($this->trade->pnl, 2);

        $pnl_status = $this->trade->pnl >= 0 ? 'profit' : 'loss';
        $pnl_sign = $this->trade->pnl >= 0 ? '+' : '-';

        // Notification message
        $content = "Your $type trade on $pairName has closed. You realized a $pnl_status of $pnl_sign\$$pnl.";

        return [
            'type' => 'trade_closed',
            'title' => 'Trade Closed Successfully',
            'content' => $content,
            'trade_details' => [
                'pair' => $this->trade->pair,
                'amount' => $this->trade->amount,
                'type' => $this->trade->type,
                'entry_price' => $this->trade->entry_price,
                'exit_price' => $this->trade->exit_price,
                'pnl' => $this->trade->pnl,
                'trading_mode' => $this->trade->trading_mode,
                'closed_at' => $this->trade->closed_at,
                'is_auto_close' => $this->trade->is_auto_close,
            ]
        ];
    }
}
