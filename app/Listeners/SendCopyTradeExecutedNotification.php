<?php

namespace App\Listeners;

use App\Events\CopyTradeExecuted;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCopyTradeExecutedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(CopyTradeExecuted $event): void
    {
        $event->user->notify(new \App\Notifications\CopyTradeExecuted(
            $event->transaction,
        ));
    }
}
