<?php

namespace App\Listeners;

use App\Events\TradingAccountDebited;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDebitedSuccessfulNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TradingAccountDebited $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->userProfile->user->email)
                    ->send(new \App\Mail\TradingAccountDebited(
                        $event->userProfile,
                        $event->toToken,
                        $event->fromAmount,
                        $event->toAmount
                    ));
            } catch (Exception $e) {
                Log::error('Failed to send trading account debited email', [
                    'email' => $event->userProfile->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
