<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class DeleteAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if ($user || $user->role === 'admin') {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm' => 'required|string|in:DELETE',
        ];
    }

    public function messages(): array
    {
        return [
            'confirm.required' => 'You must type "DELETE" to confirm.',
            'confirm.string' => 'You must type "DELETE" to confirm.',
            'confirm.in' => 'You must type "DELETE" to confirm.',
        ];
    }
}
