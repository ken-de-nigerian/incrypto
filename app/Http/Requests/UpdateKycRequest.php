<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ensure the user owns the submission they are trying to update
        $submission = $this->route('submission');
        return $submission && $this->user()->id === $submission->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:25'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'id_proof_type' => ['required', Rule::in(['national_id', 'passport', 'driving_license'])],
            'id_front_proof' => ['nullable', 'file', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'id_back_proof' => ['nullable', 'file', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'address_proof_type' => ['required', Rule::in(['electricity_bill', 'bank_statement', 'tenancy_agreement'])],
            'address_front_proof' => ['nullable', 'file', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'terms' => ['accepted'],
        ];
    }
}
