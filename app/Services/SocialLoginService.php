<?php

namespace App\Services;

use App\Events\UserReferred;
use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Log;
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
    public function findOrCreateUser(string $provider, SocialiteUser $socialUser, string $referrerData): User
    {
        return DB::transaction(function () use ($provider, $socialUser, $referrerData) {
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
                : $this->createUser($provider, $socialUser, $referrerData);
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
    protected function createUser(string $provider, SocialiteUser $socialUser, string $referrerData): User
    {
        $walletBalance = $this->walletService->initializeNewUserWallet();
        if ($walletBalance === false) {
            throw new Exception(__('Failed to initialize wallet balance.'));
        }

        [$firstname, $lastname] = $this->extractNames($socialUser);

        $localAvatarPath = $this->downloadAndStoreAvatar($socialUser);

        $user = User::create([
            'ref_by' => $referrerData ?: null,
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
            'profile_photo_path' => $localAvatarPath,
            'referral_code' => $this->generateUniqueReferralCode(),
        ]);

        // Fire the Registered event so a listener can handle the welcome email
        event(new Registered($user));

        $referrerId = $referrerData;
        if ($referrerId && $referrer = User::find($referrerId)) {
            // Dispatch the event ONLY if a referrer exists
            event(new UserReferred($user, $referrer));
        }

        return $user;
    }

    /**
     * Downloads the social user's avatar and stores it locally.
     *
     * @param SocialiteUser $socialUser
     * @return string|null The path to the stored file, or null on failure.
     */
    protected function downloadAndStoreAvatar(SocialiteUser $socialUser): ?string
    {
        $avatarUrl = $socialUser->getAvatar();
        if (!$avatarUrl) {
            return null;
        }

        try {
            $fileContents = Http::get($avatarUrl)->body();
            $filename = 'avatars/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($filename, $fileContents);
            return asset('storage/' . $filename);
        } catch (Throwable $e) {
            Log::error("Failed to download socialite avatar: " . $e->getMessage(), ['url' => $avatarUrl]);
            return null;
        }
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
