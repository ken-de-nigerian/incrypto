<?php

namespace App\Events;

use App\Models\KycSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycSubmitted
{
    use Dispatchable, SerializesModels;

    public KycSubmission $submission;

    public function __construct(KycSubmission $submission)
    {
        $this->submission = $submission;
    }
}
