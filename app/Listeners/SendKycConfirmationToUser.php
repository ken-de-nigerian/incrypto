<?php
namespace App\Listeners;

use App\Events\KycSubmitted;
use App\Mail\KycSubmissionReceived;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendKycConfirmationToUser
{
    /**
     * Handle the event.
     */
    public function handle(KycSubmitted $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->submission->user->email)
                    ->send(new KycSubmissionReceived($event->submission));
            } catch (Exception $e) {
                Log::error('Failed to send kyc submitted email', [
                    'email' => $event->submission->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
