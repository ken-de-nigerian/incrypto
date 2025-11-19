<?php

namespace Database\Seeders;

use App\Models\MasterTrader;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterTraderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Expertise levels distribution
        $expertiseLevels = [
            'Newcomer' => 15,
            'Growing talent' => 20,
            'High achiever' => 15,
            'Expert' => 8,
            'Legend' => 5,
        ];

        // Sample bios for different expertise levels
        $bios = [
            'Newcomer' => [
                'New to trading but eager to learn and share my journey.',
                'Starting my trading career with a focus on risk management.',
                'Learning the markets one trade at a time.',
            ],
            'Growing talent' => [
                'Experienced in forex and crypto markets. Focus on technical analysis.',
                'Building a consistent trading strategy with moderate risk.',
                'Passionate about swing trading and momentum strategies.',
            ],
            'High achiever' => [
                'Professional trader with 5+ years experience. Specializing in day trading.',
                'Consistent returns through disciplined risk management and technical analysis.',
                'Focus on major currency pairs and commodities with proven track record.',
            ],
            'Expert' => [
                'Former hedge fund trader. Expert in algorithmic trading strategies.',
                '10+ years of trading experience. Specializing in options and derivatives.',
                'Professional trader with institutional background. Focus on market fundamentals.',
            ],
            'Legend' => [
                'Veteran trader with 15+ years experience managing million-dollar portfolios.',
                'International trading champion. Expert in multiple asset classes.',
                'Former investment bank trader. Proven track record of exceptional returns.',
            ],
        ];

        $createdCount = 0;

        foreach ($expertiseLevels as $expertise => $count) {
            for ($i = 0; $i < $count; $i++) {
                // Get or create a user for this master trader
                $user = User::inRandomOrder()->first() ?? User::factory()->create();

                // Calculate realistic metrics based on expertise
                $metrics = $this->getMetricsForExpertise($expertise);

                MasterTrader::create([
                    'user_id' => $user->id,
                    'expertise' => $expertise,
                    'risk_score' => $metrics['risk_score'],
                    'gain_percentage' => $metrics['gain_percentage'],
                    'copiers_count' => $metrics['copiers_count'],
                    'commission_rate' => $metrics['commission_rate'],
                    'total_profit' => $metrics['total_profit'],
                    'total_loss' => $metrics['total_loss'],
                    'is_active' => rand(0, 100) > 10, // 90% active
                    'bio' => $bios[$expertise][array_rand($bios[$expertise])],
                    'total_trades' => $metrics['total_trades'],
                    'win_rate' => $metrics['win_rate'],
                    'created_at' => now()->subDays(rand(1, 365)),
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Created $createdCount master traders successfully!");
    }

    /**
     * Get realistic metrics based on expertise level.
     */
    private function getMetricsForExpertise(string $expertise): array
    {
        return match ($expertise) {
            'Newcomer' => [
                'risk_score' => rand(1, 3),
                'gain_percentage' => rand(-500, 1500) / 100, // -5% to 15%
                'copiers_count' => rand(0, 10),
                'commission_rate' => rand(5, 10),
                'total_profit' => rand(0, 5000),
                'total_loss' => rand(0, 3000),
                'total_trades' => rand(10, 100),
                'win_rate' => rand(40, 60),
            ],
            'Growing talent' => [
                'risk_score' => rand(2, 5),
                'gain_percentage' => rand(500, 3000) / 100, // 5% to 30%
                'copiers_count' => rand(10, 50),
                'commission_rate' => rand(8, 15),
                'total_profit' => rand(5000, 20000),
                'total_loss' => rand(2000, 10000),
                'total_trades' => rand(100, 500),
                'win_rate' => rand(55, 65),
            ],
            'High achiever' => [
                'risk_score' => rand(3, 7),
                'gain_percentage' => rand(2000, 5000) / 100, // 20% to 50%
                'copiers_count' => rand(50, 150),
                'commission_rate' => rand(10, 20),
                'total_profit' => rand(20000, 100000),
                'total_loss' => rand(5000, 30000),
                'total_trades' => rand(500, 2000),
                'win_rate' => rand(60, 72),
            ],
            'Expert' => [
                'risk_score' => rand(4, 8),
                'gain_percentage' => rand(4000, 8000) / 100, // 40% to 80%
                'copiers_count' => rand(100, 300),
                'commission_rate' => rand(15, 25),
                'total_profit' => rand(100000, 500000),
                'total_loss' => rand(20000, 100000),
                'total_trades' => rand(1000, 5000),
                'win_rate' => rand(68, 78),
            ],
            'Legend' => [
                'risk_score' => rand(5, 10),
                'gain_percentage' => rand(7000, 15000) / 100, // 70% to 150%
                'copiers_count' => rand(200, 1000),
                'commission_rate' => rand(20, 30),
                'total_profit' => rand(500000, 2000000),
                'total_loss' => rand(50000, 300000),
                'total_trades' => rand(3000, 10000),
                'win_rate' => rand(75, 85),
            ],
            default => [
                'risk_score' => rand(1, 5),
                'gain_percentage' => rand(0, 2000) / 100,
                'copiers_count' => rand(0, 50),
                'commission_rate' => rand(5, 15),
                'total_profit' => rand(0, 10000),
                'total_loss' => rand(0, 5000),
                'total_trades' => rand(10, 500),
                'win_rate' => rand(45, 65),
            ],
        };
    }
}
