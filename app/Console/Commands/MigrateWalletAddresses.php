<?php

namespace App\Console\Commands;

use App\Models\WalletAddress;
use Illuminate\Console\Command;

class MigrateWalletAddresses extends Command
{
    protected $signature = 'wallet:migrate-from-env';
    protected $description = 'Migrate wallet addresses from .env to database';

    public function handle(): int
    {
        $envWallets = env('WALLET_ADDRESSES');

        if (!$envWallets) {
            $this->error('No WALLET_ADDRESSES found in .env');
            return 1;
        }

        $wallets = json_decode($envWallets, true);

        if (!$wallets) {
            $this->error('Invalid JSON in WALLET_ADDRESSES');
            return 1;
        }

        $this->info('Migrating ' . count($wallets) . ' wallets...');

        foreach ($wallets as $wallet) {
            WalletAddress::updateOrCreate(
                ['method_code' => $wallet['method_code']],
                [
                    'name' => $wallet['name'],
                    'abbreviation' => $wallet['abbreviation'],
                    'gateway_parameter' => $wallet['gateway_parameter'],
                    'status' => $wallet['status'],
                    'coingecko_id' => $wallet['coingecko_id'],
                ]
            );
        }

        $this->info('✅ Migration complete!');
        return 0;
    }
}
