<?php

namespace App\Events;

use App\Models\CopyTradeTransaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CopyTradeClosed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public CopyTradeTransaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, CopyTradeTransaction $transaction)
    {
        $this->user = $user;
        $this->transaction = $transaction;
    }
}
