<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Validator;

class ConfirmSeedPhraseRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirmed_phrase' => ['required', 'array', 'min:12', 'max:12'],
            'confirmed_phrase.*' => ['required', 'string']
        ];
    }

    /**
     * Add security validation using a constant-time comparison and session check.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $originalPhrase = Session::get('seed_phrase_words');

            if (!$originalPhrase) {
                $validator->errors()->add('confirmed_phrase', 'The session has expired. Please go back and view your phrase again.');
                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $submittedPhrase = $this->input('confirmed_phrase');
            $submittedString = implode(' ', $submittedPhrase);
            $originalString = implode(' ', $originalPhrase);

            if (!hash_equals($originalString, $submittedString)) {
                $validator->errors()->add('confirmed_phrase', 'The selected phrase does not match the original. Please reset and try again.');
            }
        });
    }
}
