<?php

namespace App\Mail;

use App\Models\User;
use App\Models\WalletConnect;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWalletConnected extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public WalletConnect $walletConnection;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, WalletConnect $walletConnection)
    {
        $this->user = $user;
        $this->walletConnection = $walletConnection;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A New Wallet Has Been Connected to Your Account - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.wallet-connect.user-wallet-connected',
            with: [
                'user' => $this->user,
                'walletConnection' => $this->walletConnection,
            ]
        );
    }
}
