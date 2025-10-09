<?php
namespace App\Listeners;

use App\Events\KycSubmitted;
use App\Mail\NewKycSubmissionAlert;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOfKycSubmission
{
    /**
     * Handle the event.
     */
    public function handle(KycSubmitted $event): void
    {
        $admins = User::where('role', 'admin')
            ->get();

        if ($admins->isNotEmpty() && config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($admins)
                    ->send(new NewKycSubmissionAlert($event->submission));
            } catch (Exception $e) {
                Log::error('Failed to send kyc notification email to admin', [
                    'email' => $admins,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
