<?php

namespace App\Models;

use App\Notifications\Auth\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @method static where(string $string, mixed $email)
 * @method static create(array $array)
 * @property mixed $role
 * @property mixed $wallet_balance
 * @property mixed $id
 * @property mixed $email
 * @property mixed $password
 * @property mixed $social_login_provider
 * @property mixed $status
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet_balance' => 'json'
        ];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->email
        ]);

        $this->notify(new ResetPasswordNotification($resetUrl));
    }

    /**
     * @return HasOne|User
     */
    public function profile(): HasOne|User
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * @return HasOne|KycSubmission
     */
    public function kyc(): HasOne|KycSubmission
    {
        return $this->hasOne(KycSubmission::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(WalletConnect::class);
    }

    public function cryptoSwaps()
    {
        return $this->hasMany(CryptoSwap::class);
    }

    public function receivedCryptos()
    {
        return $this->hasMany(ReceivedCrypto::class);
    }

    public function sentCryptos()
    {
        return $this->hasMany(SendCrypto::class);
    }

    public function referralLink(): string
    {
        $referralCode = optional($this->profile)->referral_code ?? '';
        return route('register', ['ref' => $referralCode]);
    }
}
