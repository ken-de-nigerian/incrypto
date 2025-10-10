<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle the registration of a new user.
     *
     * @param array $data The validated data from the request.
     * @return User
     * @throws Exception
     * @throws Throwable
     */
    public function registerUser(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $walletBalance = $this->walletService->initializeNewUserWallet();
            if ($walletBalance === false) {
                throw new Exception(__('Failed to initialize wallet balance.'));
            }

            $user = User::create([
                'wallet_balance' => $walletBalance,
                'first_name' => strtoupper($data['first_name']),
                'last_name' => strtoupper($data['last_name']),
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'password' => Hash::make($data['password']),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'country' => $data['country'],
            ]);

            // Fire the Registered event so a listener can handle the welcome email
            event(new Registered($user));

            return $user;
        });
    }
}
