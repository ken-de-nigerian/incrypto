<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CancelInvestmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'payout_option' => [
                'required',
                'string',
                Rule::in(['capital_and_interest', 'capital_only', 'interest_only', 'nothing'])
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payout_option.required' => 'Please select a payout option',
            'payout_option.in' => 'Invalid payout option selected',
            'cancellation_reason.max' => 'Cancellation reason cannot exceed 500 characters',
        ];
    }
}
