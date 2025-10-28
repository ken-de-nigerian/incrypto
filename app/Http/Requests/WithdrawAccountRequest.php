<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class WithdrawAccountRequest extends FormRequest
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
            'target_symbol' => ['required', 'string', 'max:10'],
            'usd_amount' => ['required', 'numeric', 'min:0.01', 'max:' . auth()->user()->profile->live_trading_balance, 'regex:/^\d+(\.\d{1,2})?$/'],
            'estimated_crypto' => ['required', 'numeric', 'min:0.00000001'],
        ];
    }
}
