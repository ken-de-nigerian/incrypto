<?php

namespace App\Listeners;

use App\Events\LoanApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLoanApprovedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(LoanApproved $event): void
    {
        $event->user->notify(new \App\Notifications\LoanApproved(
            $event->loan,
        ));
    }
}
