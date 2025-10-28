<?php

namespace App\Events;

use App\Models\UserProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TradingAccountDebited
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public UserProfile $userProfile;
    public string $toToken;
    public string $fromAmount;
    public string $toAmount;

    /**
     * Create a new event instance.
     *
     * @param UserProfile $userProfile
     * @param string $toToken
     * @param string $fromAmount
     * @param string $toAmount
     */
    public function __construct(UserProfile $userProfile, string $toToken, string $fromAmount, string $toAmount)
    {
        $this->userProfile = $userProfile;
        $this->toToken = $toToken;
        $this->fromAmount = $fromAmount;
        $this->toAmount = $toAmount;
    }
}
