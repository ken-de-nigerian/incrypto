<?php

namespace App\Events;

use App\Models\CryptoSwap;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CryptoSwapped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CryptoSwap $swapRecord;

    /**
     * Create a new event instance.
     *
     * @param CryptoSwap $swapRecord
     */
    public function __construct(CryptoSwap $swapRecord)
    {
        $this->swapRecord = $swapRecord;
    }
}
