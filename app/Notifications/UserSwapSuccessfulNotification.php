<?php

namespace App\Notifications;

use App\Models\CryptoSwap;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserSwapSuccessfulNotification extends Notification
{
    use Queueable;

    public CryptoSwap $swap;

    public function __construct(CryptoSwap $swap)
    {
        $this->swap = $swap;
    }

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $fromAmount = rtrim(rtrim(number_format($this->swap->from_amount, 8), '0'), '.');
        $toAmount = rtrim(rtrim(number_format($this->swap->to_amount, 8), '0'), '.');

        return (new MailMessage)
            ->subject('Your Crypto Swap was Successful!')
            ->view('emails.swap.user-swap-alert', [
                'swap' => $this->swap,
                'from_amount' => $fromAmount,
                'to_amount' => $toAmount
            ]);
    }

    public function toDatabase(): array
    {
        $fromAmount = rtrim(rtrim(number_format($this->swap->from_amount, 8), '0'), '.');
        $toAmount = rtrim(rtrim(number_format($this->swap->to_amount, 8), '0'), '.');
        $message = "You successfully swapped $fromAmount {$this->swap->from_token} for $toAmount {$this->swap->to_token}.";

        return [
            'type' => 'crypto_swap',
            'swap_id' => $this->swap->id,
            'title' => 'Swap Successful',
            'content' => $message,
        ];
    }
}
