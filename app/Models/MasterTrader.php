<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterTrader extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'expertise',
        'risk_score',
        'gain_percentage',
        'copiers_count',
        'commission_rate',
        'total_profit',
        'total_loss',
        'is_active',
        'bio',
        'total_trades',
        'win_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'risk_score' => 'integer',
        'gain_percentage' => 'decimal:2',
        'copiers_count' => 'integer',
        'commission_rate' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'total_loss' => 'decimal:2',
        'is_active' => 'boolean',
        'total_trades' => 'integer',
        'win_rate' => 'decimal:2',
    ];

    /**
     * Get the user that owns the master trader profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all copy trades for this master trader.
     */
    public function copyTrades(): HasMany
    {
        return $this->hasMany(CopyTrade::class);
    }

    /**
     * Get active copy trades for this master trader.
     */
    public function activeCopyTrades(): HasMany
    {
        return $this->hasMany(CopyTrade::class)->where('status', 'active');
    }

    /**
     * Scope a query to only include active master traders.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by expertise level.
     */
    public function scopeByExpertise($query, string $expertise)
    {
        return $query->where('expertise', $expertise);
    }

    /**
     * Scope a query to order by gain percentage.
     */
    public function scopeTopPerformers($query)
    {
        return $query->orderBy('gain_percentage', 'desc');
    }

    /**
     * Scope a query to order by copiers count.
     */
    public function scopeMostCopied($query)
    {
        return $query->orderBy('copiers_count', 'desc');
    }

    /**
     * Get the net profit/loss.
     */
    public function getNetProfitAttribute(): float
    {
        return $this->total_profit - $this->total_loss;
    }

    /**
     * Update copiers count based on active copy trades.
     */
    public function updateCopiersCount(): void
    {
        $this->copiers_count = $this->activeCopyTrades()->count();
        $this->save();
    }

    /**
     * Calculate and update win rate.
     */
    public function updateWinRate(): void
    {
        if ($this->total_trades > 0) {
            // You'll need to implement logic to track winning trades
            // This is a placeholder
            $this->win_rate = 0; // Calculate based on your business logic
            $this->save();
        }
    }
}
