<?php

namespace App\Services;

use App\Events\KycApproved;
use App\Models\KycSubmission;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApproveUserKycService
{
    /**
     * @throws Throwable
     */
    public function approve(KycSubmission $kyc): void
    {
        DB::transaction(function () use ($kyc) {
            event(new KycApproved($kyc));
            $kyc->update([
                'status' => 'verified',
                'rejection_reason' => null,
            ]);
        });
    }
}
