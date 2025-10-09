<?php

namespace App\Events;

use App\Models\SendCrypto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CryptoSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SendCrypto $sendCrypto;

    /**
     * Create a new event instance.
     *
     * @param SendCrypto $sendCrypto
     * @return void
     */
    public function __construct(SendCrypto $sendCrypto)
    {
        $this->sendCrypto = $sendCrypto;
    }
}
