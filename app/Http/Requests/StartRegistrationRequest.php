<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * We'll use this to check if registration is enabled.
     */
    public function authorize(): bool
    {
        return config('settings.register.enabled', false);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:255|unique:users,email',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        return redirect()->back()->with('error', __('Registration disabled. Please contact the administrator.'));
    }
}
