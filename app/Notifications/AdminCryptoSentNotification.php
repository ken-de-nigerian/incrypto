<?php

namespace App\Notifications;

use App\Models\SendCrypto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminCryptoSentNotification extends Notification
{
    use Queueable;

    protected SendCrypto $sendCrypto;

    public function __construct(SendCrypto $sendCrypto)
    {
        $this->sendCrypto = $sendCrypto;
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $user = $this->sendCrypto->user;

        return (new MailMessage)
            ->subject('New Crypto Transaction Initiated by User')
            ->view('emails.sent.admin-crypto-sent-alert', [
                'transaction' => $this->sendCrypto,
                'user' => $user,
            ]);
    }
}
