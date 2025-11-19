<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanTimeSetting;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, seed the plan_time_settings table with unique periods
        $timeSettingsData = [
            [
                'name' => '1 Hour',
                'period' => 1,
            ],
            [
                'name' => '2 Hours',
                'period' => 2,
            ],
            [
                'name' => '3 Hours',
                'period' => 3,
            ],
            [
                'name' => '10 Hours',
                'period' => 10,
            ],
            [
                'name' => '12 Hours',
                'period' => 12,
            ],
        ];

        $timeSettings = [];
        foreach ($timeSettingsData as $data) {
            $setting = PlanTimeSetting::firstOrCreate(
                ['period' => $data['period']],
                $data
            );
            $timeSettings[$data['period']] = $setting->id;
        }

        // Now seed the plans, linking to the appropriate plan_time_settings_id
        $plans = [
            [
                'name' => 'Starter Plan',
                'minimum' => 10.00,
                'maximum' => 1000.00,
                'interest' => 10.00,
                'period' => 1,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 24,
            ],
            [
                'name' => 'Premium Plan',
                'minimum' => 100.00,
                'maximum' => 5000.00,
                'interest' => 15.00,
                'period' => 3,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 1,
            ],
            [
                'name' => 'Gold Plan',
                'minimum' => 50.00,
                'maximum' => 100.00,
                'interest' => 10.00,
                'period' => 1,
                'status' => 'active',
                'capital_back_status' => 'no',
                'repeat_time' => 1,
            ],
            [
                'name' => 'Elite Lifetime Plan',
                'minimum' => 500.00,
                'maximum' => 1000.00,
                'interest' => 10.00,
                'period' => 12,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 1,
            ],
            [
                'name' => 'Basic Fixed Plan',
                'minimum' => 20.00,
                'maximum' => 30.00,
                'interest' => 1.00,
                'period' => 2,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 1,
            ],
            [
                'name' => 'Diamond Plan',
                'minimum' => 200.00,
                'maximum' => 10000.00,
                'interest' => 20.00,
                'period' => 1,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 2,
            ],
            [
                'name' => 'Silver Plan',
                'minimum' => 10.00,
                'maximum' => 50.00,
                'interest' => 5.00,
                'period' => 2,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 1,
            ],
            [
                'name' => 'Platinum Plan',
                'minimum' => 500.00,
                'maximum' => 25000.00,
                'interest' => 25.00,
                'period' => 3,
                'status' => 'active',
                'capital_back_status' => 'no',
                'repeat_time' => 3,
            ],
            [
                'name' => 'Bronze Plan',
                'minimum' => 5.00,
                'maximum' => 20.00,
                'interest' => 2.00,
                'period' => 1,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 1,
            ],
            [
                'name' => 'VIP Plan',
                'minimum' => 1000.00,
                'maximum' => 50000.00,
                'interest' => 30.00,
                'period' => 12,
                'status' => 'active',
                'capital_back_status' => 'yes',
                'repeat_time' => 1,
            ],
        ];

        foreach ($plans as $plan) {
            $plan['plan_time_settings_id'] = $timeSettings[$plan['period']];
            Plan::create($plan);
        }
    }
}
