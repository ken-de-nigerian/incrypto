<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CopyTrade extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'master_trader_id',
        'current_profit',
        'current_loss',
        'total_commission_paid',
        'status',
        'started_at',
        'paused_at',
        'stopped_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_profit' => 'decimal:2',
        'current_loss' => 'decimal:2',
        'total_commission_paid' => 'decimal:2',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    /**
     * Get the user that owns the copy trade.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the master trader being copied.
     */
    public function masterTrader(): BelongsTo
    {
        return $this->belongsTo(MasterTrader::class);
    }

    /**
     * Get all transactions for this copy trade.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CopyTradeTransaction::class);
    }

    /**
     * Scope a query to only include active copy trades.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include paused copy trades.
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope a query to only include stopped copy trades.
     */
    public function scopeStopped($query)
    {
        return $query->where('status', 'stopped');
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by master trader.
     */
    public function scopeByMasterTrader($query, int $masterTraderId)
    {
        return $query->where('master_trader_id', $masterTraderId);
    }

    /**
     * Get the net profit/loss.
     */
    public function getNetProfitLossAttribute(): float
    {
        return $this->current_profit - $this->current_loss;
    }

    /**
     * Check if the copy trade is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the copy trade is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if the copy trade is stopped.
     */
    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    /**
     * Pause the copy trade.
     */
    public function pause(): bool
    {
        $this->status = 'paused';
        $this->paused_at = now();
        return $this->save();
    }

    /**
     * Resume the copy trade.
     */
    public function resume(): bool
    {
        $this->status = 'active';
        $this->paused_at = null;
        return $this->save();
    }

    /**
     * Stop the copy trade.
     */
    public function stop(): bool
    {
        $this->status = 'stopped';
        $this->stopped_at = now();
        return $this->save();
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($copyTrade) {
            if (!$copyTrade->started_at) {
                $copyTrade->started_at = now();
            }
        });

        static::created(function ($copyTrade) {
            // Update master trader's copiers count
            $copyTrade->masterTrader->updateCopiersCount();
        });

        static::deleted(function ($copyTrade) {
            // Update master trader's copiers count
            $copyTrade->masterTrader->updateCopiersCount();
        });
    }
}
