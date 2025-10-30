<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    protected $fillable = [
        'user_id',
        'pair',
        'pair_name',
        'type',
        'status',
        'entry_price',
        'amount',
        'leverage',
        'stop_loss',
        'take_profit',
        'pnl',
        'opened_at',
        'closed_at'
    ];

    protected $casts = [
        'entry_price' => 'decimal:4',
        'amount' => 'decimal:2',
        'pnl' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
