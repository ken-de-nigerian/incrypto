<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\RegistrationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    protected RegistrationService $registrationService;

    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Show the email verification form.
     */
    public function verify(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('verification_email');

        if (!$email || !$this->registrationService->isVerificationPending($email)) {
            return redirect()
                ->route('register')
                ->with('error', __('Your verification session has expired or is invalid. Please request a new one.'));
        }

        return Inertia::render('Auth/Verify');
    }

    /**
     * Handle OTP verification.
     */
    public function store(VerifyOtpRequest $request): RedirectResponse
    {
        $email = $request->session()->get('verification_email');

        if (!$email || !$this->registrationService->isVerificationPending($email)) {
            return redirect()
                ->route('register')
                ->with('error', __('Your verification session has expired or is invalid.'));
        }

        if (!$this->registrationService->verifyCode($email, $request->validated('otp'))) {
            return back()->withErrors(['otp' => __('Invalid verification code.')]);
        }

        // Clear the verification data
        $this->registrationService->clearVerificationData($email);
        $request->session()->forget('verification_email');

        // Store verified email for the next step (onboarding)
        $request->session()->put('verified_email', $email);

        return redirect()->route('onboarding')->with([
            'success' => __('Your email has been verified successfully.'),
        ]);
    }

    /**
     * Resend OTP to the user's email.
     */
    public function resend(Request $request): JsonResponse
    {
        $email = $request->session()->get('verification_email');

        if (!$email || !$this->registrationService->isVerificationPending($email)) {
            return response()->json(['error' => __('Your verification session has expired or is invalid.')], 422);
        }

        // Throttle resend attempts
        $throttleKey = 'resend-otp|' . $email;
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'error' => __('Please wait :seconds seconds before retrying.', ['seconds' => $seconds]),
                'retryAfter' => $seconds
            ], 429);
        }

        try {

            $this->registrationService->sendVerificationCode($email);

            $throttleDelay = 60;
            RateLimiter::hit($throttleKey, $throttleDelay);

            return response()->json([
                'status' => 'success',
                'message' => __('A new OTP has been sent to your email.'),
                'retryAfter' => $throttleDelay
            ]);
        } catch (Exception $e) {
            Log::error('Failed to resend OTP', ['email' => $email, 'error' => $e->getMessage()]);
            return response()->json(['error' => __('Failed to send the code. Please try again later.')], 500);
        }
    }
}
