<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ExecuteLoanRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'loan_amount' => 'required|numeric|min:100',
            'tenure_months' => 'required|integer|min:1',
            'interest_rate' => 'required|numeric',
            'monthly_emi' => 'required|numeric',
            'total_interest' => 'required|numeric',
            'total_payment' => 'required|numeric',
            'loan_reason' => 'required|string',
            'loan_collateral' => 'required|string',
        ];
    }
}
