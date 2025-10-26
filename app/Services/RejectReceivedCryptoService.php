<?php

namespace App\Services;

use App\Models\ReceivedCrypto;
use Illuminate\Support\Facades\DB;
use Throwable;

class RejectReceivedCryptoService
{
    /**
     * @throws Throwable
     */
    public function reject(string $id)
    {
        $crypto = ReceivedCrypto::findOrFail($id);
        return DB::transaction(function () use ($crypto) {
            $crypto->update(['status' => 'failed']);
        });
    }
}
