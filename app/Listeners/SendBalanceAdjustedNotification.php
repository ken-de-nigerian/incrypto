<?php

namespace App\Listeners;

use App\Events\BalanceAdjusted;
use App\Notifications\BalanceAdjustedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBalanceAdjustedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(BalanceAdjusted $event): void
    {
        $event->user->notify(new BalanceAdjustedNotification(
            $event->token,
            $event->amount,
            $event->reason,
            $event->actionType,
        ));
    }
}
