<?php

namespace App\Listeners;

use App\Events\TradeClosed;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTradeClosedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TradeClosed $event): void
    {
        $event->user->notify(new \App\Notifications\TradeClosed(
            $event->trade,
        ));
    }
}
