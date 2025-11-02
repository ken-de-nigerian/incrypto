<?php

namespace App\Listeners;

use App\Events\ForexTradeClosed;
use App\Notifications\TradeClosed;

class SendForexTradeClosedNotification
{
    /**
     * Handle the event.
     */
    public function handle(ForexTradeClosed $event): void
    {
        $event->user->notify(new TradeClosed(
            $event->trade,
        ));
    }
}
