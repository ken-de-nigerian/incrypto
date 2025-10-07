<?php

namespace App\Listeners;

use App\Mail\MailPasswordResetConfirmation;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class SendPasswordResetConfirmation
{
    /**
     * Handle the event.
     */
    public function handle(PasswordReset $event): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($event->user->email)
                    ->send(new MailPasswordResetConfirmation(
                        $event->user,
                        Request::ip(),
                        $this->getDevice(Request::userAgent())
                    ));
            } catch (Exception $e) {
                Log::error('Failed to send password reset confirmation email', [
                    'email' => $event->user->email,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function getDevice(?string $userAgent): string
    {
        if (!$userAgent) return 'Unknown Device';
        $parser = new Agent();
        $parser->setUserAgent($userAgent);
        return $parser->device() . ' (' . $parser->platform() . ') - ' . $parser->browser();
    }
}
