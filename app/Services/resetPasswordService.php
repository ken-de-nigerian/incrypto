<?php

namespace App\Services;

use App\Events\PasswordUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class resetPasswordService
{
    public function resetPassword(User $user, array $data): void
    {
        $user->forceFill([
            'password' => Hash::make($data['password'])
        ]);

        $user->setRememberToken(Str::random(60));
        $user->save();

        // Dispatch the event
        event(new PasswordUpdated($user));
    }
}
