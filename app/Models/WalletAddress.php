<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WalletAddress extends Model
{
    protected $fillable = [
        'method_code',
        'name',
        'abbreviation',
        'gateway_parameter',
        'status',
        'coingecko_id'
    ];

    protected $casts = [
        'gateway_parameter' => 'encrypted',
        'status' => 'boolean',
    ];

    /**
     * Decrypt gateway_parameter without throwing. Returns null when the value was
     * encrypted with a different APP_KEY, so one bad row can't break every gateway.
     */
    public function safeGatewayParameter(): ?string
    {
        try {
            return $this->gateway_parameter;
        } catch (DecryptException $e) {
            Log::warning('Unable to decrypt gateway_parameter; re-save this wallet in admin', [
                'method_code' => $this->method_code,
                'name' => $this->name,
            ]);
            return null;
        }
    }

    public static function getFormattedWallets()
    {
        return self::where('status', 1)
            ->get()
            ->map(function ($wallet) {
                return [
                    'method_code' => $wallet->method_code,
                    'name' => $wallet->name,
                    'abbreviation' => $wallet->abbreviation,
                    'gateway_parameter' => $wallet->safeGatewayParameter(),
                    'status' => (string) $wallet->status,
                    'coingecko_id' => $wallet->coingecko_id,
                ];
            })
            // Never offer users a deposit method without a readable address
            ->filter(fn ($wallet) => $wallet['gateway_parameter'] !== null)
            ->values()
            ->toArray();
    }
}
