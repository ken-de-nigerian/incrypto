<?php

namespace Database\Seeders;

use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UserSeeder extends Seeder
{
    /**
     * @throws Throwable
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Check if admin already exists
            $admin = User::where('email', 'admin@web-traxa.com')->first();

            if ($admin) {
                $this->command->warn('Admin user already exists. Skipping...');

                // Check if profile exists
                if (!$admin->userProfile) {
                    $this->command->info('Creating missing profile for admin...');
                    $admin->profile()->create([
                        'referral_code' => strtoupper(Str::random(10)),
                        'profile_photo_path' => null,
                        'address' => null,
                        'country' => null,
                        'seed_phrase' => null,
                        'seed_phrase_status' => 'skipped',
                        'seed_phrase_skipped_at' => now(),
                        'seed_phrase_expires_at' => null,
                    ]);
                    $this->command->info('Admin profile created successfully.');
                } else {
                    $this->command->info('Admin profile already exists.');
                }
            } else {
                // Create admin user with profile
                $this->command->info('Creating admin user...');

                $admin = User::factory()
                    ->admin()
                    ->create();

                $this->command->info('Admin user created. Creating profile...');

                $admin->profile()->create([
                    'referral_code' => strtoupper(Str::random(10)),
                    'profile_photo_path' => null,
                    'address' => null,
                    'country' => null,
                    'seed_phrase' => null,
                    'seed_phrase_status' => 'skipped',
                    'seed_phrase_skipped_at' => now(),
                    'seed_phrase_expires_at' => null,
                ]);

                $this->command->info('Admin user and profile created successfully.');
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            $this->command->error('Error creating admin user: ' . $e->getMessage());
            Log::error('UserSeeder failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
