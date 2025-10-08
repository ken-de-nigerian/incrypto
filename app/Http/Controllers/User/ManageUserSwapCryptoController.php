<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\GatewayHandlerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use JsonException;

class ManageUserSwapCryptoController extends Controller
{
    /**
     * Display the swap page.
     */
    public function index()
    {
        return Inertia::render('User/Swap', $this->getSwapData());
    }

    /**
     * Fetches and structures data for the swap component.
     *
     * @return array
     */
    private function getSwapData(): array
    {
        $user = Auth::user();
        $tokens = [];
        $userBalances = [];
        $prices = [];

        try {
            $userWallets = json_decode($user->wallet_balance, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $userWallets = [];
            Log::error("Failed to decode user wallet balance for user $user->id: " . $e->getMessage());
        }

        foreach ($userWallets as $symbol => $crypto) {
            $userBalances[$symbol] = (float) ($crypto['balance'] ?? 0);
        }

        $marketData = (new GatewayHandlerService())->fetchGatewaysCrypto();

        foreach ($marketData as $crypto) {
            $coin = $crypto['coin'];
            $symbol = strtoupper($coin['symbol']);

            $tokens[] = [
                'symbol' => $symbol,
                'name' => $coin['name'] ?? 'Unknown',
                'logo' => $coin['image'] ?? asset('assets/images/default-crypto.png'),
                'address' => '0x' . substr(md5($symbol), 0, 100),
                'decimals' => 18,
                'chain' => 'Ethereum',
            ];

            $prices[$symbol] = (float) ($coin['current_price'] ?? 0);
        }

        return [
            'tokens' => $tokens,
            'userBalances' => (object) $userBalances,
            'prices' => (object) $prices,
        ];
    }
}
