<?php

namespace App\Services;

use App\Notifications\SendVerificationCode;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class RegistrationService
{
    /**
     * Generate, store, and send a verification code to the given email.
     *
     * @param string $email
     * @return bool
     */
    public function sendVerificationCode(string $email): bool
    {
        $token = $this->generateToken();
        $expiration = Carbon::now()->addMinutes(10);

        Cache::put($this->cacheKey($email), $token, $expiration);

        $notifiable = (new class {
            use Notifiable;
            public string $email;
            public function routeNotificationForMail(){return $this->email;}
        });

        $notifiable->email = $email;

        Notification::send($notifiable, new SendVerificationCode($token));

        return true;
    }

    /**
     * Generate a 6-character alphanumeric token.
     */
    protected function generateToken(): string
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        return substr(str_shuffle($characters), 0, 6);
    }

    /**
     * Check if a verification process is currently active for an email.
     */
    public function isVerificationPending(string $email): bool
    {
        return Cache::has($this->cacheKey($email));
    }

    /**
     * Verify the provided OTP against the cached token.
     */
    public function verifyCode(string $email, string $otp): bool
    {
        $cachedToken = Cache::get($this->cacheKey($email));
        if (is_null($cachedToken)) {
            return false;
        }

        return hash_equals((string) $cachedToken, $otp);
    }

    /**
     * Clear verification data from the cache.
     */
    public function clearVerificationData(string $email): void
    {
        Cache::forget($this->cacheKey($email));
    }

    /**
     * Generate the cache key for verification.
     */
    protected function cacheKey(string $email): string
    {
        return 'verify:' . $email;
    }
}
