<?php
namespace App\Listeners;

use App\Events\KycSubmitted;
use App\Mail\NewKycSubmissionAlert;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOfKycSubmission implements ShouldQueue
{
    public function handle(KycSubmitted $event): void
    {
        $adminEmail = config('settings.site.site_email');

        if ($adminEmail && config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($adminEmail)
                    ->send(new NewKycSubmissionAlert($event->submission));
            } catch (Exception $e) {
                Log::error('Failed to send kyc notification email to admin', [
                    'email' => $adminEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
