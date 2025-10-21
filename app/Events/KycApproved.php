<?php

namespace App\Events;

use App\Models\KycSubmission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public KycSubmission $kyc;

    /**
     * Create a new event instance.
     */
    public function __construct(KycSubmission $kyc)
    {
        $this->kyc = $kyc;
    }
}
