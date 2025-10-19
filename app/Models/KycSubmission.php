<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KycSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'first_name',
        'last_name',
        'phone_number',
        'date_of_birth',
        'country',
        'state',
        'city',
        'address',
        'id_proof_type',
        'id_front_proof_path',
        'id_back_proof_path',
        'address_proof_type',
        'address_front_proof_path',
        'rejection_reason',
    ];

    protected function idFrontProofUrl(): Attribute
    {
        return Attribute::get(fn () => $this->id_front_proof_path
            ? Storage::url($this->id_front_proof_path)
            : null
        );
    }

    protected function idBackProofUrl(): Attribute
    {
        return Attribute::get(fn () => $this->id_back_proof_path
            ? Storage::url($this->id_back_proof_path)
            : null
        );
    }

    protected function addressFrontProofUrl(): Attribute
    {
        return Attribute::get(fn () => $this->address_front_proof_path
            ? Storage::url($this->address_front_proof_path)
            : null
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
