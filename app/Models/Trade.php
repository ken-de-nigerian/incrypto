<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category',
        'pair',
        'pair_name',
        'type',
        'amount',
        'leverage',
        'duration',
        'entry_price',
        'exit_price',
        'status',
        'pnl',
        'trading_mode',
        'is_demo_forced_win',
        'opened_at',
        'closed_at',
        'expiry_time',
        'is_auto_close'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'leverage' => 'integer',
        'amount' => 'decimal:2',
        'entry_price' => 'decimal:8',
        'exit_price' => 'decimal:8',
        'pnl' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'expiry_time' => 'datetime',
        'is_auto_close' => 'boolean',
        'is_demo_forced_win' => 'boolean',
    ];

    /**
     * Get the user that owns the trade.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
