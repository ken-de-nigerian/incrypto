<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'confirm' => 'required|string|in:DELETE',
        ];
    }
}
