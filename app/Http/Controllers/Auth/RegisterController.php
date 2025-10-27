<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartRegistrationRequest;
use App\Services\RegistrationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Show the application registration form.
     */
    public function index(Request $request): Response
    {
        $request->session()->put('referral', $request->query('ref'));
        return Inertia::render('Auth/Register');
    }

    /**
     * Validate the user's email before proceeding to onboard them.
     */
    public function register(StartRegistrationRequest $request, RegistrationService $registrationService): RedirectResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];

        // Throttle settings
        if (RateLimiter::tooManyAttempts($this->throttleKey($email), 1)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($email));
            return back()->withErrors([
                'email' => __('Please wait a minute before retrying.'),
                'throttle' => $seconds
            ]);
        }

        try {

            $registrationService->sendVerificationCode($email);

            RateLimiter::hit($this->throttleKey($email)); // 60-second cooldown
            $request->session()->put('verification_email', $email);

            return $this->notify('success', __('A verification code has been sent to your email.'))
                ->toRoute('verify');

        } catch (Exception $e) {
            Log::error(__('Failed to send OTP email'), ['email' => $email, 'error' => $e->getMessage()]);
            return $this->notify('error', __('Failed to send the code. Please try again later.'))->toBack();

        }
    }

    /**
     * Generate the throttle key.
     */
    protected function throttleKey(string $email): string
    {
        return 'verify-email|' . strtolower($email) . '|' . request()->ip();
    }
}
