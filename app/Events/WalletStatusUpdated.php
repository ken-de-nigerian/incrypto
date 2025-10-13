<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $walletKey;
    public string $status;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $walletKey, string $status)
    {
        $this->user = $user;
        $this->walletKey = $walletKey;
        $this->status = $status;
    }
}
