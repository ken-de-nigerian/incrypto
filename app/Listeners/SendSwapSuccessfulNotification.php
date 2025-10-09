<?php

namespace App\Listeners;

use App\Events\CryptoSwapped;
use App\Models\User;
use App\Notifications\AdminSwapSuccessfulNotification;
use App\Notifications\UserSwapSuccessfulNotification;
use Illuminate\Support\Facades\Notification;

class SendSwapSuccessfulNotification
{
    /**
     * Handle the event.
     */
    public function handle(CryptoSwapped $event): void
    {
        $user = $event->swapRecord->user;
        $user->notify(new UserSwapSuccessfulNotification($event->swapRecord));

        Notification::send(User::where('role', 'admin')->get(),
            new AdminSwapSuccessfulNotification($event->swapRecord)
        );
    }
}
