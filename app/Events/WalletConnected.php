<?php

namespace App\Events;

use App\Models\User;
use App\Models\WalletConnect;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletConnected
{
    use Dispatchable, SerializesModels;

    public User $user;
    public WalletConnect $walletConnection;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, WalletConnect $walletConnection) {
        $this->user = $user;
        $this->walletConnection = $walletConnection;
    }
}
