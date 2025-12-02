<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'loan_amount',
        'interest_rate',
        'tenure_months',
        'monthly_emi',
        'total_interest',
        'total_payment',
        'loan_reason',
        'loan_collateral',
        'status',
        'disbursed_at',
        'repayed_at',
        'due_date',
        'remarks',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_emi' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'total_payment' => 'decimal:2',
        'disbursed_at' => 'datetime',
        'repayed_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
