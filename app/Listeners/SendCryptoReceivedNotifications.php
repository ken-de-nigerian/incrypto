<?php

namespace App\Listeners;

use App\Events\CryptoReceived;
use App\Models\User;
use App\Notifications\NewReceivedCryptoTransactionAdminNotify;
use Illuminate\Support\Facades\Notification;

class SendCryptoReceivedNotifications
{
    /**
     * Handle the event.
     */
    public function handle(CryptoReceived $event): void
    {
        Notification::send(User::where('role', 'admin')->get(),
            new NewReceivedCryptoTransactionAdminNotify($event->receivedCrypto)
        );
    }
}
