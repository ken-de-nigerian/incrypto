<?php

namespace App\Services;

use App\Events\UserReferred;
use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
                'ref_by' => $data['ref_by'],
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
                'referral_code' => $this->generateUniqueReferralCode(),
            ]);

            // Fire the Registered event so a listener can handle the welcome email
            event(new Registered($user));

            $referrerId = $data['ref_by'];
            if ($referrerId && $referrer = User::find($referrerId)) {
                // Dispatch the event ONLY if a referrer exists
                event(new UserReferred($user, $referrer));
            }

            return $user;
        });
    }

    /**
     * Generate a unique referral code.
     *
     * @param int $length The desired length of the code.
     * @return string
     */
    function generateUniqueReferralCode(int $length = 8): string
    {
        do {
            // Generate a random, mixed-case alphanumeric string
            $code = Str::random($length);

            // Check if the code already exists in the database
        } while (UserProfile::where('referral_code', $code)->exists());

        return $code;
    }
}
