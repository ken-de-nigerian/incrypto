<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Prepare the data for validation.
     * This is where we'll sanitize the phone number.
     */
    protected function prepareForValidation(): void
    {
        if ($this->phone_number) {
            $this->merge([
                'phone_number' => str_replace(' ', '', $this->phone_number),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'min:10', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'country' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,gif', 'max:5120'],
        ];
    }
}
