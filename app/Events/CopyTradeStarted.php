<?php

namespace App\Events;

use App\Models\CopyTrade;
use App\Models\MasterTrader;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CopyTradeStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public array $data;
    public CopyTrade $copyTrade;
    public MasterTrader $masterTrader;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $data, CopyTrade $copyTrade, MasterTrader $masterTrader)
    {
        $this->user = $user;
        $this->data = $data;
        $this->copyTrade = $copyTrade;
        $this->masterTrader = $masterTrader;
    }
}
