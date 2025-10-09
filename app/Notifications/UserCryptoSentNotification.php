<?php

namespace App\Notifications;

use App\Models\SendCrypto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCryptoSentNotification extends Notification
{
    use Queueable;

    protected SendCrypto $sendCrypto;

    public function __construct(SendCrypto $sendCrypto)
    {
        $this->sendCrypto = $sendCrypto;
    }

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Crypto Sent Transaction Initiated')
            ->view('emails.sent.user-crypto-sent-alert', [
                'transaction' => $this->sendCrypto,
            ]);
    }

    public function toArray(): array
    {
        $message = "Your transfer of {$this->sendCrypto->amount} {$this->sendCrypto->token_symbol} to wallet address {$this->sendCrypto->recipient_address} has been initiated.";

        return [
            'type' => 'crypto_sent',
            'sent_id' => $this->sendCrypto->id,
            'title' => 'Crypto Transfer Initiated',
            'content' => $message,
        ];
    }
}
