<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AdjustUserBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'actionType' => [
                'required',
                'string',
                'in:credit,debit',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
            'reason' => [
                'nullable',
                'string',
                'min:5',
                'max:500',
            ],
            'wallet_symbol' => [
                'required',
                'string',
                'alpha_dash',
                'max:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'actionType.required' => 'The action type is required.',
            'actionType.in' => 'The action type must be either credit or debit.',
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.gt' => 'The amount must be greater than 0.',
            'amount.regex' => 'The amount can have a maximum of 8 decimal places.',
            'reason.required' => 'A reason is required.',
            'reason.min' => 'The reason must be at least 5 characters.',
            'reason.max' => 'The reason cannot exceed 500 characters.',
            'wallet_symbol.required' => 'The wallet is required.',
            'wallet_symbol.alpha_dash' => 'The wallet must only contain letters, numbers, and dashes.',
            'wallet_symbol.max' => 'The wallet symbol cannot exceed 10 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim($this->reason),
            'wallet_symbol' => strtoupper(trim($this->wallet_symbol)),
        ]);
    }
}
