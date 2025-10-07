<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'string'],
            'wallet_name' => ['required', 'string', 'max:255'],
            'wallet_phrase' => ['required', 'string', 'min:12'],
        ];
    }
}
