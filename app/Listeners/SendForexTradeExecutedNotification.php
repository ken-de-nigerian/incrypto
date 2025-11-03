<?php

namespace App\Listeners;

use App\Events\ForexTradeExecuted;
use App\Notifications\TradeExecuted;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendForexTradeExecutedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ForexTradeExecuted $event): void
    {
        $event->user->notify(new TradeExecuted(
            $event->data,
            $event->expiryTime,
        ));
    }
}
