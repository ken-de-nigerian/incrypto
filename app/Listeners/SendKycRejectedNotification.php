<?php

namespace App\Listeners;

use App\Events\KycRejected;
use App\Notifications\KycRejectedNotification;

class SendKycRejectedNotification
{
    /**
     * Handle the event.
     */
    public function handle(KycRejected $event): void
    {
        $user = $event->kyc->user;
        $user->notify(new KycRejectedNotification($event->kyc, $event->data));
    }
}
