<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Loan;

class LoanSeeder extends Seeder
{
    public function run(): void
    {
        // Check if users exist first
        $users = User::all();

        if ($users->count() === 0) {
            $this->command->info('No users found. Creating some test users first...');
            $users = User::factory(5)->create();
        }

        // Create loans for each user
        foreach ($users as $user) {
            Loan::factory()
                ->count(rand(1, 3)) // Assign 1 to 3 loans per user
                ->create([
                    'user_id' => $user->id
                ]);
        }

        $this->command->info('Loans seeded successfully for existing users.');
    }
}
