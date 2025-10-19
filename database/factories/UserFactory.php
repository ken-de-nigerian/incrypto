<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\GatewayHandlerService;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    // NO $walletService property is needed here.

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        // CHANGE: Call the method directly on $this (the Factory instance)
        $walletBalance = $this->initializeNewUserWallet();

        if ($walletBalance === false) {
            // It's better to log the failure and return dummy data in a seeder
            // rather than throwing an exception and halting the entire seed process.
            Log::error('Seeder: Failed to initialize wallet balance, using dummy data.');
            $walletBalance = json_encode(['USD' => 1000.00, 'BTC' => 0.05, 'ETH' => 0.5]);
            // Alternatively: throw new Exception(__('Failed to initialize wallet balance.'));
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->faker->unique()->safeEmail(),
            'wallet_balance' => $walletBalance,
            'phone_number' => $this->faker->unique()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // password
            'role' => $this->faker->randomElement(['user', 'user', 'user', 'admin']),
            'status' => $this->faker->randomElement(['active', 'suspended']),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Helper to determine the network from a currency name.
     */
    protected function getNetworkFromName(string $name): string
    {
        if (str_contains($name, 'TRC 20')) return 'TRC20';
        if (str_contains($name, 'ERC 20')) return 'ERC20';
        if (str_contains($name, 'BEP 20')) return 'BEP20';
        return 'Native'; // Default or other network
    }

    /**
     * Initializes a new user's wallet structure.
     */
    public function initializeNewUserWallet(): string|false
    {
        try {
            // Instantiate the service directly inside the method
            $gateways = new GatewayHandlerService();
            $cryptocurrencies = $gateways->getGateways();
            $formattedCryptos = [];

            foreach ($cryptocurrencies as $crypto) {
                $id = $crypto['method_code'];
                $symbol = strtoupper($crypto['abbreviation']);
                $name = $crypto['name'];
                $key = $symbol;
                $status = $crypto['status'];

                // Logic to set a specific key for USDT networks
                if (str_contains($name, 'TRC 20')) $key = 'USDT_TRC20';
                elseif (str_contains($name, 'ERC 20')) $key = 'USDT_ERC20';
                elseif (str_contains($name, 'BEP 20')) $key = 'USDT_BEP20';

                $formattedCryptos[$key] = [
                    'id' => $id,
                    'name' => $name,
                    'symbol' => $symbol,
                    // Use the protected helper method on $this
                    'network' => $this->getNetworkFromName($name),
                    'balance' => $this->faker->randomFloat(4, 0.0001, 100), // Use faker for random balance
                    'status' => $status
                ];
            }

            return json_encode($formattedCryptos);
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Wallet creation failed in UserFactory', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
