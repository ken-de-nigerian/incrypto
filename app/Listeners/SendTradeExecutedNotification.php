<?php

namespace App\Listeners;

use App\Events\TradeExecuted;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTradeExecutedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TradeExecuted $event): void
    {
        $event->user->notify(new \App\Notifications\TradeExecuted(
            $event->data,
            $event->expiryTime,
        ));
    }
}
