<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WalletAddress;
use App\Services\GatewayHandlerService;
use Illuminate\Console\Command;

class RefreshWalletImages extends Command
{
    protected $signature = 'wallet:refresh-images';
    protected $description = "Update the coin images stored in users' wallet balances to the current provider's logos";

    public function handle(GatewayHandlerService $gatewayHandler): int
    {
        if (empty($gatewayHandler->getCryptos())) {
            $this->error('Could not fetch the crypto list; no images were changed.');
            return 1;
        }

        // Only reads non-encrypted columns, so wallets with unreadable addresses are included
        $imagesByMethodCode = WalletAddress::all(['method_code', 'abbreviation', 'coingecko_id'])
            ->mapWithKeys(fn($wallet) => [
                $wallet->method_code => $gatewayHandler->getCoinImage($wallet->coingecko_id, $wallet->abbreviation),
            ])
            ->filter()
            ->all();

        $this->info('Resolved images for ' . count($imagesByMethodCode) . ' wallets. Updating users...');
        $updated = 0;

        User::chunkById(500, function ($users) use ($imagesByMethodCode, &$updated) {
            foreach ($users as $user) {
                $walletBalance = $user->wallet_balance;
                $wallets = is_string($walletBalance)
                    ? json_decode($walletBalance, true)
                    : (is_array($walletBalance) ? $walletBalance : []);

                if (!is_array($wallets)) {
                    continue;
                }

                $changed = false;
                foreach ($wallets as $key => $wallet) {
                    $image = $imagesByMethodCode[$wallet['id'] ?? ''] ?? null;
                    if ($image && ($wallet['image'] ?? null) !== $image) {
                        $wallets[$key]['image'] = $image;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $user->update(['wallet_balance' => json_encode($wallets)]);
                    $updated++;
                }
            }
        });

        $this->info("Updated wallet images for $updated users.");
        return 0;
    }
}
