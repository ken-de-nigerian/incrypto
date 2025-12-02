<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static updateOrCreate(array $array, array $profileData)
 * @method static create(array $array)
 */
class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'user_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected $casts = [
        'live_trading_balance' => 'decimal:2',
        'demo_trading_balance' => 'decimal:2',
        'network_fee' => 'decimal:8',
        'charge_network_fee' => 'boolean'
    ];

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
