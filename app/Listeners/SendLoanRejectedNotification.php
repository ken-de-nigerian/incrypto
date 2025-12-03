<?php

namespace App\Listeners;

use App\Events\LoanRejected;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLoanRejectedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(LoanRejected $event): void
    {
        $event->user->notify(new \App\Notifications\LoanRejected(
            $event->loan,
        ));
    }
}
