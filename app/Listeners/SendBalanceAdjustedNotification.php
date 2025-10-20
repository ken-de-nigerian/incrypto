<?php

namespace App\Listeners;

use App\Events\BalanceAdjusted;
use App\Notifications\BalanceAdjustedNotification;

class SendBalanceAdjustedNotification
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
