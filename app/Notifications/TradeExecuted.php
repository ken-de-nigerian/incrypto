<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TradeExecuted extends Notification
{
    use Queueable;

    public array $data;
    public string $expiryTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data, string $expiryTime)
    {
        $this->data = $data;
        $this->expiryTime = $expiryTime;
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
        $pairName = $this->data['pair_name'] ?? $this->data['pair'];
        $type = ucfirst($this->data['type']);

        $subject = "$type Trade Executed: $pairName (Expiry: $this->expiryTime)";

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.forex_trade_executed', [
                'user' => $notifiable,
                'expiryTime' => $this->expiryTime,
                'data' => $this->data
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $pairName = $this->data['pair_name'] ?? $this->data['pair'];
        $amount = number_format($this->data['amount'], 2);
        $type = ucfirst($this->data['type']);

        return [
            'type' => 'trade_executed',
            'title' => 'Trade Executed Successfully',
            'content' => "A $type trade for $pairName with an amount of \$$amount has been opened.",
            'trade_details' => [
                'pair' => $this->data['pair'],
                'amount' => $this->data['amount'],
                'type' => $this->data['type'],
                'duration' => $this->data['duration'],
                'entry_price' => $this->data['entry_price'],
                'trading_mode' => $this->data['trading_mode'],
                'expiry_time' => $this->expiryTime,
            ]
        ];
    }
}
