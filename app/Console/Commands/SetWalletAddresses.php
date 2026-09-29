<?php

namespace App\Console\Commands;

use App\Models\WalletAddress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetWalletAddresses extends Command
{
    protected $signature = 'wallet:set-addresses
                            {pairs* : One or more method_code=address pairs}
                            {--dry-run : Show what would change without saving}';

    protected $description = "Set wallet deposit addresses by method_code (works even when the old address can't be decrypted)";

    public function handle(): int
    {
        $updates = [];
        foreach ($this->argument('pairs') as $pair) {
            [$methodCode, $address] = array_pad(explode('=', $pair, 2), 2, '');
            $methodCode = trim($methodCode);
            $address = trim($address);

            if ($methodCode === '' || $address === '') {
                $this->error("Invalid pair \"$pair\"; expected method_code=address.");
                return 1;
            }
            $wallet = WalletAddress::where('method_code', $methodCode)->first();
            if (!$wallet) {
                $this->error("No wallet with method_code $methodCode; nothing was saved.");
                return 1;
            }
            $updates[] = [$wallet, $address];
        }

        $this->table(
            ['Method code', 'Wallet', 'Old address', 'New address'],
            array_map(fn($u) => [
                $u[0]->method_code,
                $u[0]->name,
                $u[0]->safeGatewayParameter() ?? '(unreadable)',
                $u[1],
            ], $updates)
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was saved.');
            return 0;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as [$wallet, $address]) {
                $wallet->forgetUnreadableGatewayParameter();
                $wallet->update(['gateway_parameter' => $address]);
            }
        });

        $this->info('Updated ' . count($updates) . ' wallet addresses.');
        return 0;
    }
}
