<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create the primary Admin user using updateOrCreate for idempotency
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'wallet_balance' => ['USD' => 100000.00, 'BTC' => 5.0, 'ETH' => 50.0],
                'phone_number' => '5551234567',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'), // Use a safe default password
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // 2. Ensure the admin has a profile
        $admin->profile()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'referral_code' => 'ADMINCODE',
                'address' => '123 Admin Lane',
                'country' => 'United States',
                'seed_phrase' => 'admin-only-master-phrase', // Use a non-standard phrase for seeding
                'seed_phrase_status' => 'generated',
            ]
        );
    }
}
