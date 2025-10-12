<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OnboardingController extends Controller
{
    /**
     * Display the onboarding form.
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function index(Request $request): RedirectResponse|Response
    {
        $verifiedEmail = $request->session()->get('verified_email');

        if (!$verifiedEmail) {
            return redirect()
                ->route('register')
                ->with('error', __('Your verification session has expired or is invalid. Please request a new one.'));
        }

        return Inertia::render('Auth/Onboarding', [
            'email' => $verifiedEmail,
        ]);
    }

    /**
     * Handle account creation.
     *
     * @param RegisterRequest $request
     * @param AuthService $authService
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(RegisterRequest $request, AuthService $authService): RedirectResponse
    {
        try {

            // Get referral info
            $referralCode = session()->get('referral');
            $referrer = null;

            if ($referralCode) {
                $referrerProfile = UserProfile::where('referral_code', $referralCode)->first();
                if ($referrerProfile) {
                    $referrer = User::find($referrerProfile->user_id);
                }
            }

            // Get referrer data
            $userData = $request->validated();
            $userData['ref_by'] = $referrer?->id;

            // The request is already validated and authorized at this point.
            $user = $authService->registerUser($userData);

            // Log in the user
            Auth::login($user);

            // Clear verification session data
            $request->session()->forget(['verified_email', 'verification_email', 'referral']);

            return redirect()->route('secure.wallet');

        } catch (Exception $exception) {
            Log::error('Account creation failed', [
                'error' => $exception->getMessage(),
                'email' => $request->input('email'),
            ]);

            return redirect()->back()->withInput()
                ->with('error', __('Failed to create account. Please try again.'));
        }
    }
}
