<?php

namespace App\Notifications;

use App\Models\ReceivedCrypto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReceivedCryptoTransactionAdminNotify extends Notification
{
    use Queueable;

    protected ReceivedCrypto $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReceivedCrypto $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('New Pending Crypto Deposit Initiated')
            ->view('emails.received.admin-new-crypto-deposit-alert', [
                'user' => $this->transaction->user,
                'transaction' => $this->transaction,
            ]);
    }
}
