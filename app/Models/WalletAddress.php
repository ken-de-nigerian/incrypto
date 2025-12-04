<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public static function getFormattedWallets()
    {
        return self::where('status', 1)
            ->get()
            ->map(function ($wallet) {
                return [
                    'method_code' => $wallet->method_code,
                    'name' => $wallet->name,
                    'abbreviation' => $wallet->abbreviation,
                    'gateway_parameter' => $wallet->gateway_parameter,
                    'status' => (string) $wallet->status,
                    'coingecko_id' => $wallet->coingecko_id,
                ];
            })
            ->toArray();
    }
}
