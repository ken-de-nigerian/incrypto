<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Throwable;

class SocialLoginService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Find an existing user or create a new one.
     * @throws Exception|Throwable
     */
    public function findOrCreateUser(string $provider, SocialiteUser $socialUser): User
    {
        return DB::transaction(function () use ($provider, $socialUser) {
            $user = User::where('social_login_id', $socialUser->getId())
                ->where('social_login_provider', $provider)
                ->first();

            if (!$user && $email = $socialUser->getEmail()) {
                $user = User::withTrashed()->where('email', $email)->first();

                if ($user && $user->trashed()) {
                    throw new Exception(__('auth.account_deleted'));
                }

                if ($user && $user->social_login_provider && $user->social_login_provider !== $provider) {
                    throw new Exception(__('auth.provider_conflict', ['provider' => $user->social_login_provider]));
                }
            }

            if ($user && $user->status === 'inactive') {
                throw new Exception(__('Your account has been suspended. Please contact support for assistance.'));
            }

            return $user
                ? $this->updateUserProvider($user, $provider, $socialUser)
                : $this->createUser($provider, $socialUser);
        });
    }

    protected function updateUserProvider(User $user, string $provider, SocialiteUser $socialUser): User
    {
        $user->update([
            'social_login_provider' => $provider,
            'social_login_id' => $socialUser->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);
        return $user;
    }

    /**
     * @throws Exception
     */
    protected function createUser(string $provider, SocialiteUser $socialUser): User
    {
        $walletBalance = $this->walletService->initializeNewUserWallet();
        if ($walletBalance === false) {
            throw new Exception(__('Failed to initialize wallet balance.'));
        }

        [$firstname, $lastname] = $this->extractNames($socialUser);

        $user = User::create([
            'email' => $socialUser->getEmail(),
            'wallet_balance' => $walletBalance,
            'first_name' => $this->sanitizeName(strtoupper($firstname)),
            'last_name' => $this->sanitizeName(strtoupper($lastname)),
            'password' => Hash::make(Str::random()),
            'social_login_provider' => $provider,
            'social_login_id' => $socialUser->getId(),
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'profile_photo_path' => $socialUser->getAvatar(),
        ]);

        // Fire the Registered event so a listener can handle the welcome email
        event(new Registered($user));

        return $user;
    }

    protected function extractNames(SocialiteUser $socialUser): array
    {
        if (isset($socialUser->user['given_name'])) {
            return [$socialUser->user['given_name'], $socialUser->user['family_name'] ?? ''];
        }
        $parts = explode(' ', $socialUser->getName() ?? '', 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    protected function sanitizeName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name)) ?: 'Unknown';
    }
}
