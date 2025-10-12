<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailPasswordResetConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public Authenticatable $user;
    public string $ip;
    public string $device;

    /**
     * Create a new message instance.
     */
    public function __construct(Authenticatable $user, string $ip, string $device)
    {
        $this->user = $user;
        $this->ip = $ip;
        $this->device = $device;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Changed Successfully',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-confirmation',
            with: [
                'user' => $this->user,
                'ip' => $this->ip,
                'device' => $this->device,
            ]
        );
    }
}
