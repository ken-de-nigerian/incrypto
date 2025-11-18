<?php

namespace App\Events;

use App\Models\InvestmentHistory;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvestmentExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public array $data;
    public InvestmentHistory $investmentHistory;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $data, InvestmentHistory $investmentHistory)
    {
        $this->user = $user;
        $this->data = $data;
        $this->investmentHistory = $investmentHistory;
    }
}
