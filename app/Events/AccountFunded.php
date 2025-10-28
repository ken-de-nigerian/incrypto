<?php

namespace App\Events;

use App\Models\UserProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountFunded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public UserProfile $userProfile;
    public string $fromToken;
    public string $fromAmount;
    public string $toAmount;

    /**
     * Create a new event instance.
     *
     * @param UserProfile $userProfile
     * @param string $fromToken
     * @param string $fromAmount
     * @param string $toAmount
     */
    public function __construct(UserProfile $userProfile, string $fromToken, string $fromAmount, string $toAmount)
    {
        $this->userProfile = $userProfile;
        $this->fromToken = $fromToken;
        $this->fromAmount = $fromAmount;
        $this->toAmount = $toAmount;
    }
}
