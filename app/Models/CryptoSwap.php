<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoSwap extends Model
{
    protected $fillable = [
        'user_id',
        'from_token',
        'to_token',
        'from_amount',
        'to_amount',
        'transaction_hash',
        'chain',
        'status',
        'notes'
    ];

    protected $casts = [
        'from_amount' => 'decimal:8',
        'to_amount' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
