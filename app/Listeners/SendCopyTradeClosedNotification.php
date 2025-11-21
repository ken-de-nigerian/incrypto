<?php

namespace App\Listeners;

use App\Events\CopyTradeClosed;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCopyTradeClosedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(CopyTradeClosed $event): void
    {
        $event->user->notify(new \App\Notifications\CopyTradeClosed(
            $event->transaction,
        ));
    }
}
