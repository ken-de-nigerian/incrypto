<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (auth()->check() && auth()->user()->role === 'admin') {
            return true;
        }

        return $this->session()->has('verified_email');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->phone_number) {
            $this->merge([
                'phone_number' => str_replace(' ', '', $this->phone_number),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isAdminSubmission = auth()->check() && auth()->user()->role === 'admin';
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'required|string|min:10|max:255|unique:users,phone_number',
            'country' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];

        if (!$isAdminSubmission) {
            $verifiedEmail = $this->session()->get('verified_email');
            $rules['email'] .= '|in:' . $verifiedEmail;
        }

        return $rules;
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'email.in' => __('The email address does not match the one that was verified.'),
        ];
    }
}
