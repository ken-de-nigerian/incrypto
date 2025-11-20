<?php

namespace App\Notifications;

use App\Models\CopyTrade;
use App\Models\MasterTrader;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CopyTradeStartedUser extends Notification
{
    use Queueable;

    public MasterTrader $masterTrader;
    public CopyTrade $copyTrade;
    public array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(MasterTrader $masterTrader, CopyTrade $copyTrade, array $data)
    {
        $this->masterTrader = $masterTrader;
        $this->copyTrade = $copyTrade;
        $this->data = $data;
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
        $traderName = $this->masterTrader->user->first_name . ' ' . $this->masterTrader->user->last_name;
        $amount = number_format($this->data['amount'] ?? $this->copyTrade->amount, 2);

        return (new MailMessage)
            ->subject('Copy Trading Started: ' . $traderName)
            ->view('emails.copy_trade_started_user', [
                'user' => $notifiable,
                'traderName' => $traderName,
                'amount' => $amount,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $traderName = $this->masterTrader->user->first_name . ' ' . $this->masterTrader->user->last_name;

        return [
            'title' => 'Copy Trading Active',
            'content' => "You are now copying $traderName with $" . number_format($this->data['amount'], 2) . " per trade.",
            'amount' => $this->data['amount'],
            'master_trader_id' => $this->masterTrader->id,
        ];
    }
}
