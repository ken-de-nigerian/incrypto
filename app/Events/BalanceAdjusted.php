<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BalanceAdjusted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $token;
    public float $amount;
    public string $reason;
    public string $actionType;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $token
     * @param float $amount
     * @param string|null $reason
     * @param string|null $actionType
     */
    public function __construct(User $user, string $token, float $amount, ?string $reason, ?string $actionType)
    {
        $this->user = $user;
        $this->token = $token;
        $this->amount = $amount;
        $this->reason = $reason;
        $this->actionType = $actionType;
    }
}
