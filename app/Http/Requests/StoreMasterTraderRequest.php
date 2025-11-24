<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreMasterTraderRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id|unique:master_traders,user_id',
            'expertise' => 'required|in:Newcomer,Growing talent,High achiever,Expert,Legend',
            'risk_score' => 'required|integer|min:1|max:10',
            'gain_percentage' => 'nullable|numeric',
            'commission_rate' => 'nullable|numeric|min:0',
            'total_profit' => 'nullable|numeric|min:0',
            'total_loss' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'bio' => 'nullable|string|max:1000',
            'total_trades' => 'nullable|integer|min:0',
            'win_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
