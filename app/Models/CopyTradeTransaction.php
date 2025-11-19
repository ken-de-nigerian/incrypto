<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopyTradeTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'copy_trade_id',
        'type',
        'amount',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the copy trade that owns the transaction.
     */
    public function copyTrade(): BelongsTo
    {
        return $this->belongsTo(CopyTrade::class);
    }

    /**
     * Scope a query to only include profit transactions (up).
     */
    public function scopeProfits($query)
    {
        return $query->where('type', 'up');
    }

    /**
     * Scope a query to only include loss transactions (down).
     */
    public function scopeLosses($query)
    {
        return $query->where('type', 'down');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order by most recent.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if the transaction is a profit.
     */
    public function isProfit(): bool
    {
        return $this->type === 'up';
    }

    /**
     * Check if the transaction is a loss.
     */
    public function isLoss(): bool
    {
        return $this->type === 'down';
    }

    /**
     * Get formatted amount with sign.
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->type === 'up' ? '+' : '-';
        return $sign . number_format($this->amount, 2);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($transaction) {
            // Update the copy trade profit/loss
            $copyTrade = $transaction->copyTrade;

            if ($transaction->type === 'up') {
                $copyTrade->current_profit += $transaction->amount;
            } else {
                $copyTrade->current_loss += $transaction->amount;
            }

            $copyTrade->save();
        });
    }
}
