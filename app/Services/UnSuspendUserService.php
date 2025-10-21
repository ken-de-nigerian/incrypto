<?php

namespace App\Services;

use App\Mail\AccountUnsuspendedConfirmation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UnSuspendUserService
{
    public function unSuspendUser(User $user): void
    {
        $user->status = 'active';
        $user->save();

        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($user->email)
                    ->send(new AccountUnsuspendedConfirmation($user));
            } catch (Exception $e) {
                Log::error('Failed to unsuspend user', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
