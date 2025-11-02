<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class executeForexRequest extends FormRequest
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
            'pair' => 'required|string|max:20',
            'pair_name' => 'required|string|max:100',
            'type' => 'required|in:Up,Down',
            'amount' => 'required|numeric|min:1',
            'duration' => 'required|string|in:1m,5m,15m,30m,1h,4h,1d',
            'entry_price' => 'required|numeric|min:0',
            'trading_mode' => 'required|in:live,demo'
        ];
    }
}
