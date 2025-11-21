<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StartCopyRequest extends FormRequest
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
        $user = Auth::user();
        return [
            'multiplier' => 'required|numeric|min:0.1|max:100',
            'commission_fee' => 'required|numeric|min:0|max:' . $user->profile->live_trading_balance,
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'multiplier.required' => 'Copy multiplier is required.',
            'multiplier.numeric' => 'Copy multiplier must be a number.',
            'multiplier.min' => 'Copy multiplier must be at least 0.1.',
            'multiplier.max' => 'Copy multiplier cannot exceed 100.',
            'commission_fee.required' => 'Commission fee is required.',
            'commission_fee.numeric' => 'Commission fee must be a valid number.',
            'commission_fee.min' => 'Commission fee cannot be negative.',
            'commission_fee.max' => 'Insufficient balance to cover the commission fee.',
        ];
    }
}
