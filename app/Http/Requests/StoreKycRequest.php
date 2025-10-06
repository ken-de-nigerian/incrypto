<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        $existingKyc = $this->user()->kyc()->whereIn('status', ['pending', 'approved'])->first();
        return !$existingKyc;
    }

    /**
     * Prepare the data for validation.
     * This is where we'll sanitize the phone number.
     */
    protected function prepareForValidation(): void
    {
        if ($this->phone_number) {
            $this->merge([
                'phone_number' => str_replace(' ', '', $this->phone_number),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'min:10', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'id_proof_type' => ['required', Rule::in(['national_id', 'passport', 'driving_license'])],
            'id_front_proof' => ['required', 'file', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'id_back_proof' => ['nullable', 'file', 'image', 'mimes:jpg,png,jpeg', 'max:2048', Rule::requiredIf($this->id_proof_type !== 'passport')],
            'address_proof_type' => ['required', Rule::in(['electricity_bill', 'bank_statement', 'tenancy_agreement'])],
            'address_front_proof' => ['required', 'file', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'terms' => ['accepted'],
        ];
    }
}
