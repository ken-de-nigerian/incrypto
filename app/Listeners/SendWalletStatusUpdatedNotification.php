<?php

namespace App\Listeners;

use App\Events\WalletStatusUpdated;
use App\Notifications\WalletStatusChangeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWalletStatusUpdatedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(WalletStatusUpdated $event): void
    {
        $event->user->notify(new WalletStatusChangeNotification(
            $event->walletKey,
            $event->status
        ));
    }
}
