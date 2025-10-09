<?php

namespace App\Events;

use App\Models\ReceivedCrypto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CryptoReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ReceivedCrypto $receivedCrypto;

    /**
     * Create a new event instance.
     *
     * @param ReceivedCrypto $receivedCrypto
     */
    public function __construct(ReceivedCrypto $receivedCrypto)
    {
        $this->receivedCrypto = $receivedCrypto;
    }
}
