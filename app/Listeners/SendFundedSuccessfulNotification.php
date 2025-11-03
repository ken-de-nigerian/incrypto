<?php

namespace App\Listeners;

use App\Events\TradingAccountFunded;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFundedSuccessfulNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TradingAccountFunded $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->userProfile->user->email)
                    ->send(new \App\Mail\TradingAccountFunded(
                        $event->userProfile,
                        $event->fromToken,
                        $event->fromAmount,
                        $event->toAmount
                    ));
            } catch (Exception $e) {
                Log::error('Failed to send trading account funded email', [
                    'email' => $event->userProfile->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
