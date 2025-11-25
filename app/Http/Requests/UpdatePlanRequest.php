<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePlanRequest extends FormRequest
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
            'plan_time_settings_id' => 'required|exists:plan_time_settings,id',
            'name' => 'required|string|max:255',
            'minimum' => 'required|numeric|min:0',
            'maximum' => 'required|numeric|min:0|gte:minimum',
            'interest' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'capital_back_status' => 'required|in:yes,no',
            'repeat_time' => 'required|integer|min:1',
        ];
    }
}
