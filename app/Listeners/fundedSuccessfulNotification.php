<?php

namespace App\Listeners;

use App\Events\AccountFunded;
use App\Mail\AccountFundedConfirmation;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class fundedSuccessfulNotification
{
    /**
     * Handle the event.
     */
    public function handle(AccountFunded $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->userProfile->user->email)
                    ->send(new AccountFundedConfirmation(
                        $event->userProfile,
                        $event->fromToken,
                        $event->fromAmount,
                        $event->toAmount
                    ));
            } catch (Exception $e) {
                Log::error('Failed to send account funded email', [
                    'email' => $event->userProfile->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
