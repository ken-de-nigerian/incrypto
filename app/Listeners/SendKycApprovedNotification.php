<?php

namespace App\Listeners;

use App\Events\KycApproved;
use App\Notifications\KycApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendKycApprovedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(KycApproved $event): void
    {
        $user = $event->kyc->user;
        $user->notify(new KycApprovedNotification($event->kyc));
    }
}
