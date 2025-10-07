<?php

namespace App\Listeners;

use App\Mail\WelcomeEmail;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailNotification
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->user->email)
                    ->send(new WelcomeEmail($event->user));
            } catch (Exception $e) {
                Log::error('Failed to send welcome email', [
                    'email' => $event->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
