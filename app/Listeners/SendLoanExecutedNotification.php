<?php

namespace App\Listeners;

use App\Events\LoanExecuted;
use App\Models\User;
use App\Notifications\AdminLoanExecutedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendLoanExecutedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(LoanExecuted $event): void
    {
        $event->user->notify(new \App\Notifications\LoanExecuted(
            $event->loan,
        ));

        Notification::send(User::where('role', 'admin')->get(),
            new AdminLoanExecutedNotification(
                $event->loan
            )
        );
    }
}
