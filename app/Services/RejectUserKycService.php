<?php

namespace App\Services;

use App\Events\KycRejected;
use App\Models\KycSubmission;
use Illuminate\Support\Facades\DB;
use Throwable;

class RejectUserKycService
{
    /**
     * @throws Throwable
     */
    public function reject(KycSubmission $kyc, array $data): void
    {
        DB::transaction(function () use ($kyc, $data) {
            event(new KycRejected($kyc, $data));
            $kyc->update([
                'status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
            ]);
        });
    }
}
