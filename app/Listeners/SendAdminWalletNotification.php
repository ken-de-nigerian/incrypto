<?php

namespace App\Listeners;

use App\Events\WalletConnected;
use App\Mail\AdminWalletConnected;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminWalletNotification
{
    /**
     * Handle the event.
     */
    public function handle(WalletConnected $event): void
    {
        $adminEmail = config('settings.site.site_email');

        if ($adminEmail && config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($adminEmail)
                    ->send(new AdminWalletConnected($event->user, $event->walletConnection));
            } catch (Exception $e) {
                Log::error('Failed to send wallet connection email to admin', [
                    'email' => $adminEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
