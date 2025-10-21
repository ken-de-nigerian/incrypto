<?php

namespace App\Events;

use App\Models\KycSubmission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public KycSubmission $kyc;
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(KycSubmission $kyc, array $data)
    {
        $this->kyc = $kyc;
        $this->data = $data;
    }
}
