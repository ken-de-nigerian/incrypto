<?php

namespace App\Listeners;

use App\Events\UserReferred;
use App\Mail\ReferralNotificationMail;
use Illuminate\Support\Facades\Mail;

class SendReferralNotification
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
