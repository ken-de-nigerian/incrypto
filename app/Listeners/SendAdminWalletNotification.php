<?php

namespace App\Listeners;

use App\Events\WalletConnected;
use App\Mail\AdminWalletConnected;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminWalletNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(WalletConnected $event): void
    {
        $admins = User::where('role', 'admin')
            ->get();

        if ($admins->isNotEmpty() && config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($admins)
                    ->send(new AdminWalletConnected($event->user, $event->walletConnection));
            } catch (Exception $e) {
                Log::error('Failed to send wallet connection email to admin', [
                    'email' => $admins,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
