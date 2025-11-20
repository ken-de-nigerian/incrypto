<?php

namespace App\Notifications;

use App\Models\CopyTrade;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CopyTradeStartedMaster extends Notification
{
    use Queueable;

    public User $copier;
    public CopyTrade $copyTrade;
    public array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $copier, CopyTrade $copyTrade, array $data)
    {
        $this->copier = $copier;
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
        $copierName = $this->copier->first_name . ' ' . $this->copier->last_name;
        $amount = number_format($this->data['amount'] ?? $this->copyTrade->amount, 2);

        return (new MailMessage)
            ->subject('New Copier Alert: ' . $copierName)
            ->view('emails.copy_trade_started_master', [
                'user' => $notifiable,
                'copierName' => $copierName,
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
        $copierName = $this->copier->first_name . ' ' . $this->copier->last_name;

        return [
            'title' => 'New Copier Gained',
            'content' => "$copierName has started copying your trades.",
            'copier_id' => $this->copier->id,
        ];
    }
}
