<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_time_settings_id',
        'name',
        'minimum',
        'maximum',
        'interest',
        'period',
        'status',
        'capital_back_status',
        'repeat_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'minimum' => 'decimal:2',
        'maximum' => 'decimal:2',
        'interest' => 'integer',
        'period' => 'integer',
        'repeat_time' => 'integer',
    ];

    public function plan_time_settings(): BelongsTo
    {
        return $this->belongsTo(PlanTimeSetting::class);
    }
}
