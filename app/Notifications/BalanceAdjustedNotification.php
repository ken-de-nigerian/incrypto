<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BalanceAdjustedNotification extends Notification
{
    use Queueable;

    public string $token;
    public float $amount;
    public ?string $reason;
    public ?string $actionType;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, float $amount, string $reason = null, string $actionType = null)
    {
        $this->token = $token;
        $this->amount = $amount;
        $this->reason = $reason;
        $this->actionType = $actionType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->actionType === 'credit'
            ? 'Credit Notification'
            : 'Debit Notification';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.balance_adjusted', [
                'user' => $notifiable,
                'token' => $this->token,
                'amount' => $this->amount,
                'action_type' => $this->actionType,
                'reason' => $this->reason,
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $subject = $this->actionType === 'credit'
            ? 'Credit Notification'
            : 'Debit Notification';

        return [
            'type' => 'balance_adjusted',
            'trx_id' => strtolower($this->token),
            'title' => $subject,
            'content' => $this->generateMessage(),
        ];
    }

    /**
     * User-friendly message for the notification.
     */
    private function generateMessage(): string
    {
        if ($this->actionType === 'credit') {
            return "$this->amount $this->token has been credited to your account.";
        }

        return "$this->amount $this->token has been debited from your account.";
    }
}
