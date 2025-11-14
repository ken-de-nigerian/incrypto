<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TradeExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public array $data;
    public string $expiryTime;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $data, string $expiryTime)
    {
        $this->user = $user;
        $this->data = $data;
        $this->expiryTime = $expiryTime;
    }
}
