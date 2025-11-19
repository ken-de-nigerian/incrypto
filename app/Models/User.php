<?php

namespace App\Models;

use App\Notifications\Auth\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    use HasFactory, Notifiable;

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

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function referralLink(): string
    {
        $referralCode = optional($this->profile)->referral_code ?? '';
        return route('register', ['ref' => $referralCode]);
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'ref_by', 'id');
    }

    public function commissionsEarned(): HasMany
    {
         return $this->hasMany(ReferralCommission::class, 'from_id', 'id');
    }

    public function investmentHistories(): HasMany
    {
        return $this->hasMany(InvestmentHistory::class);
    }

    /**
     * Get all copy trades for this user.
     */
    public function copyTrades(): HasMany
    {
        return $this->hasMany(CopyTrade::class);
    }

    /**
     * Get active copy trades for this user.
     */
    public function activeCopyTrades(): HasMany
    {
        return $this->hasMany(CopyTrade::class)->where('status', 'active');
    }

    /**
     * Get the master trader profile for this user.
     */
    public function masterTrader()
    {
        return $this->hasOne(MasterTrader::class);
    }

    /**
     * Check if user is a master trader.
     */
    public function isMasterTrader(): bool
    {
        return $this->masterTrader()->exists();
    }

    /**
     * Get total profit from all copy trades.
     */
    public function getTotalCopyTradeProfitAttribute(): float
    {
        return $this->copyTrades()
                ->sum('current_profit') - $this->copyTrades()
                ->sum('current_loss');
    }
}
