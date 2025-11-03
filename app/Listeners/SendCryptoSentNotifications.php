<?php

namespace App\Listeners;

use App\Events\CryptoSent;
use App\Models\User;
use App\Notifications\AdminCryptoSentNotification;
use App\Notifications\UserCryptoSentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendCryptoSentNotifications implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(CryptoSent $event): void
    {
        $user = $event->sendCrypto->user;
        $user->notify(new UserCryptoSentNotification($event->sendCrypto));

        Notification::send(User::where('role', 'admin')->get(),
            new AdminCryptoSentNotification($event->sendCrypto)
        );
    }
}
