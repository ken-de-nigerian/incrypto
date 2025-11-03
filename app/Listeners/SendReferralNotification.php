<?php

namespace App\Listeners;

use App\Events\UserReferred;
use App\Mail\ReferralNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendReferralNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UserReferred $event): void
    {
        Mail::to($event->referrer->email)->send(
            new ReferralNotificationMail($event->referrer, $event->newUser)
        );
    }
}
