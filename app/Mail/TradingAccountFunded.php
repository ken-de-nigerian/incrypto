<?php

namespace App\Mail;

use App\Models\UserProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TradingAccountFunded extends Mailable
{
    use Queueable, SerializesModels;

    public UserProfile $userProfile;
    public string $fromToken;
    public string $fromAmount;
    public string $toAmount;

    /**
     * Create a new message instance.
     */
    public function __construct(UserProfile $userProfile, string $fromToken, string $fromAmount, string $toAmount)
    {
        $this->userProfile = $userProfile;
        $this->fromToken = $fromToken;
        $this->fromAmount = $fromAmount;
        $this->toAmount = $toAmount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your trading account deposit confirmation',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account-funded-confirmation',
            with: [
                'user' => $this->userProfile->user,
                'from_token' => $this->fromToken,
                'from_amount' => $this->fromAmount,
                'to_amount' => $this->toAmount,
            ]
        );
    }
}
