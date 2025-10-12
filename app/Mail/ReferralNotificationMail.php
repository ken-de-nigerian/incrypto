<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $referrer;
    public User $newUser;

    /**
     * Create a new message instance.
     */
    public function __construct(User $referrer, User $newUser)
    {
        $this->referrer = $referrer;
        $this->newUser = $newUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have a new referred user!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.referral.new_signup',
            with: [
                'referrerName' => $this->referrer->first_name,
                'newUserName' => $this->newUser->first_name ?? $this->newUser->email,
            ],
        );
    }
}
