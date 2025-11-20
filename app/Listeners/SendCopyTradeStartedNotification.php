<?php

namespace App\Listeners;

use App\Events\CopyTradeStarted;
use App\Notifications\CopyTradeStartedMaster;
use App\Notifications\CopyTradeStartedUser;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCopyTradeStartedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(CopyTradeStarted $event): void
    {
        // Notify the copier (User)
        $event->user->notify(new CopyTradeStartedUser(
            $event->masterTrader,
            $event->copyTrade,
            $event->data
        ));

        // Notify the Master Trader
        $event->masterTrader->user?->notify(new CopyTradeStartedMaster(
            $event->user,
            $event->copyTrade,
            $event->data
        ));
    }
}
