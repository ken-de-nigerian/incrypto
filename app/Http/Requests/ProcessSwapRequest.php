<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProcessSwapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'fromToken' => 'required|string',
            'toToken' => 'required|string',
            'fromAmount' => 'required|numeric|min:0',
            'toAmount' => 'required|numeric|min:0',
            'walletAddress' => 'required|string',
            'chain' => 'required|string',
            'slippage' => 'required|numeric|min:0|max:10',
            'deadline' => 'required|integer|min:1|max:60',
            'gasPreset' => 'required|string|in:low,medium,high',
        ];
    }
}
