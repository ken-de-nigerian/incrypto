<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class ExecuteInvestmentRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0.01',
            'plan_id' => 'required|exists:plans,id',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $planId = $this->input('plan_id');
            $amount = $this->input('amount');

            if ($planId && $amount) {
                $plan = Plan::find($planId);

                if ($plan) {
                    // Check if plan is active
                    if ($plan->status !== 'active') {
                        $validator->errors()->add('plan_id', 'This investment plan is not currently active.');
                    }

                    // Check minimum amount
                    if ($amount < $plan->minimum) {
                        $validator->errors()->add('amount', "The minimum investment amount for this plan is $" . number_format($plan->minimum, 2) . ".");
                    }

                    // Check maximum amount
                    if ($amount > $plan->maximum) {
                        $validator->errors()->add('amount', "The maximum investment amount for this plan is $" . number_format($plan->maximum, 2) . ".");
                    }

                    // Check if user is in live mode
                    $user = Auth::user();
                    if ($user && $user->profile->trading_status !== 'live') {
                        $validator->errors()->add('trading_status', 'Investments can only be made in Live Mode. Please switch to Live Mode to continue.');
                    }

                    // Check if user has sufficient balance
                    if ($user && $user->profile->trading_status === 'live') {
                        $liveBalance = is_string($user->profile->live_trading_balance)
                            ? (float) $user->profile->live_trading_balance
                            : $user->profile->live_trading_balance;

                        if ($amount > $liveBalance) {
                            $validator->errors()->add('amount', 'Insufficient live trading balance. Your current balance is $' . number_format($liveBalance, 2) . '.');
                        }
                    }
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Investment amount is required.',
            'amount.numeric' => 'Investment amount must be a valid number.',
            'amount.min' => 'Investment amount must be greater than zero.',
            'plan_id.required' => 'Please select an investment plan.',
            'plan_id.exists' => 'The selected investment plan does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'investment amount',
            'plan_id' => 'investment plan',
        ];
    }
}
