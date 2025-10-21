<?php

namespace App\Services;

use App\Events\AccountDeleted;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteUserAccountService
{
    /**
     * @throws Throwable
     */
    public function destroy(User $user): void
    {
        DB::transaction(function () use ($user) {
            event(new AccountDeleted($user));
            $user->delete();
        });
    }
}
