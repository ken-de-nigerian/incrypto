<?php

namespace App\Mail;

use App\Models\UserProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TradingAccountDebited extends Mailable
{
    use Queueable, SerializesModels;

    public UserProfile $userProfile;
    public string $toToken;
    public string $fromAmount;
    public string $toAmount;

    /**
     * Create a new message instance.
     */
    public function __construct(UserProfile $userProfile, string $toToken, string $fromAmount, string $toAmount)
    {
        $this->userProfile = $userProfile;
        $this->toToken = $toToken;
        $this->fromAmount = $fromAmount;
        $this->toAmount = $toAmount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your withdrawal request has been completed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account-debited-confirmation',
            with: [
                'user' => $this->userProfile->user,
                'to_token' => $this->toToken,
                'from_amount' => $this->fromAmount,
                'to_amount' => $this->toAmount,
            ]
        );
    }
}
