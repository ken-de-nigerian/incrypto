<?php

namespace Database\Seeders;

use App\Models\CopyTrade;
use App\Models\MasterTrader;
use App\Models\User;
use Illuminate\Database\Seeder;

class CopyTradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all master traders
        $masterTraders = MasterTrader::all();

        if ($masterTraders->isEmpty()) {
            $this->command->error('No master traders found. Please run MasterTraderSeeder first.');
            return;
        }

        $createdCount = 0;
        $totalCopyTrades = 200; // Total number of copy trades to create

        for ($i = 0; $i < $totalCopyTrades; $i++) {
            // Get or create a user
            $user = User::inRandomOrder()->first() ?? User::factory()->create();

            // Select a random master trader
            $masterTrader = $masterTraders->random();

            // Determine status (70% active, 15% paused, 15% stopped)
            $statusRoll = rand(1, 100);
            if ($statusRoll <= 70) {
                $status = 'active';
            } elseif ($statusRoll <= 85) {
                $status = 'paused';
            } else {
                $status = 'stopped';
            }

            // Calculate start date (within last year)
            $startedAt = now()->subDays(rand(1, 365));

            // Calculate profit/loss based on master trader's performance and duration
            $daysActive = now()->diffInDays($startedAt);
            $baseProfit = ($masterTrader->gain_percentage / 100) * rand(1000, 50000);
            $profitFactor = $daysActive / 365; // Scale by time active

            $currentProfit = max(0, $baseProfit * $profitFactor * rand(80, 120) / 100);
            $currentLoss = max(0, abs($baseProfit * 0.3) * $profitFactor * rand(50, 150) / 100);

            // Calculate commission (if profitable)
            $netProfit = $currentProfit - $currentLoss;
            $commissionPaid = 0;
            if ($netProfit > 0 && $masterTrader->commission_rate) {
                $commissionPaid = ($netProfit * $masterTrader->commission_rate) / 100;
            }

            // Set pause/stop dates based on status
            $pausedAt = null;
            $stoppedAt = null;

            if ($status === 'paused') {
                $pausedAt = $startedAt->copy()->addDays(rand(1, $daysActive));
            } elseif ($status === 'stopped') {
                $stoppedAt = $startedAt->copy()->addDays(rand(1, $daysActive));
            }

            CopyTrade::create([
                'user_id' => $user->id,
                'master_trader_id' => $masterTrader->id,
                'current_profit' => round($currentProfit, 2),
                'current_loss' => round($currentLoss, 2),
                'total_commission_paid' => round($commissionPaid, 2),
                'status' => $status,
                'started_at' => $startedAt,
                'paused_at' => $pausedAt,
                'stopped_at' => $stoppedAt,
                'created_at' => $startedAt,
                'updated_at' => now(),
            ]);

            $createdCount++;
        }

        // Update all master traders' copiers count
        foreach ($masterTraders as $masterTrader) {
            $masterTrader->updateCopiersCount();
        }

        $this->command->info("Created $createdCount copy trades successfully!");
        $this->command->info("Updated copiers count for all master traders.");
    }
}
