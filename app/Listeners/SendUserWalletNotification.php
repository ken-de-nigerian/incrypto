<?php

namespace App\Listeners;

use App\Events\WalletConnected;
use App\Mail\UserWalletConnected;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendUserWalletNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(WalletConnected $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->user->email)
                    ->send(new UserWalletConnected($event->user, $event->walletConnection));
            } catch (Exception $e) {
                Log::error('Failed to send account deleted email', [
                    'email' => $event->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
