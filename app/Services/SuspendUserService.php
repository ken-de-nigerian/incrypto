<?php

namespace App\Services;

use App\Mail\AccountSuspendedConfirmation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SuspendUserService
{
    public function suspendUser(User $user, array $data): void
    {
        $user->status = 'suspended';
        $user->save();

        if (config('settings.email_notification')) {
            try {
                Mail::mailer(config('settings.email_provider'))
                    ->to($user->email)
                    ->send(new AccountSuspendedConfirmation($user, $data));
            } catch (Exception $e) {
                Log::error('Failed to suspend user', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
