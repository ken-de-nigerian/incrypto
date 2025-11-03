<?php

namespace App\Listeners;

use App\Events\AccountDeleted;
use App\Mail\AccountDeletedConfirmation;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAccountDeletionNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(AccountDeleted $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->user->email)
                    ->send(new AccountDeletedConfirmation($event->user));
            } catch (Exception $e) {
                Log::error('Failed to send account deleted email', [
                    'email' => $event->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
