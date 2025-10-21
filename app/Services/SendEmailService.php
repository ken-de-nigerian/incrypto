<?php

namespace App\Services;

use App\Mail\UserEmailConfirmation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailService
{
    public function sendEmail(User $user, array $data): void
    {
        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($user->email)
                    ->send(new UserEmailConfirmation($user, $data));
            } catch (Exception $e) {
                Log::error('Failed to send email', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
