<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserReferred
{
    use Dispatchable, SerializesModels;

    public User $newUser;
    public User $referrer;

    /**
     * Create a new event instance.
     *
     * @param User $newUser The user who just registered.
     * @param User $referrer The user who referred them.
     * @return void
     */
    public function __construct(User $newUser, User $referrer)
    {
        $this->newUser = $newUser;
        $this->referrer = $referrer;
    }
}
