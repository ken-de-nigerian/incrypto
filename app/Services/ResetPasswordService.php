<?php

namespace App\Services;

use App\Events\PasswordUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordService
{
    public function resetPassword(User $user, array $data): void
    {
        $user->update([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ]);

        // Dispatch the event
        event(new PasswordUpdated($user));
    }
}
