<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletConnect extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_connects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'wallet_name',
        'wallet_phrase',
        'wallet_logo',
        'security_type',
        'anonymity_level',
        'ease_of_use',
        'validation_type',
        'supported_coins',
        'platforms',
        'wallet_features',
        'affiliate_url',
        'connected_at',
        'last_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'supported_coins' => 'array',
        'platforms' => 'array',
        'wallet_features' => 'array',
        'connected_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the user that owns the wallet connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
