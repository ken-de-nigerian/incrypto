<?php

namespace App\Events;

use App\Models\InvestmentHistory;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvestmentPayout
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public InvestmentHistory $investment;
    public array $payoutData;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, InvestmentHistory $investment, array $payoutData)
    {
        $this->user = $user;
        $this->investment = $investment;
        $this->payoutData = $payoutData;
    }
}
