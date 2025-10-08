<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $marketData = $this['market_data'];
        $walletData = $this['wallet_data'];
        $gateway = $this['gateway'];

        return [
            'symbol' => $this['symbol'],
            'name' => $marketData['name'] ?? 'Unknown Token',
            'logo' => $marketData['image'] ?? asset('assets/images/default-crypto.png'),
            'address' => $gateway['gateway_parameter'] ?? null,
            'decimals' => $marketData['decimals'] ?? 18,
            'chain' => $walletData['chain'] ?? 'Ethereum',
            'price_change_24h' => $marketData['price_change_percentage_24h'] ?? 0,
        ];
    }
}
