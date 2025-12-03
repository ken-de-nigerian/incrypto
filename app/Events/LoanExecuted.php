<?php

namespace App\Events;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Loan $loan;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Loan $loan)
    {
        $this->user = $user;
        $this->loan = $loan;
    }
}
