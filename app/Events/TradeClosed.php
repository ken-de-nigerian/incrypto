<?php

namespace App\Events;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TradeClosed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Trade $trade;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Trade $trade)
    {
        $this->user = $user;
        $this->trade = $trade;
    }
}
