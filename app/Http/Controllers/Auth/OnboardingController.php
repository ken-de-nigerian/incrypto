<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OnboardingController extends Controller
{
    /**
     * Where to redirect users after registration.
     *
     * @return string
     */
    protected function redirectTo(): string
    {
        if (Gate::allows('access-admin-dashboard')) {
            return route('admin.dashboard');
        }

        if (Gate::allows('access-user-dashboard')) {
            return route('user.dashboard');
        }

        Auth::logout();
        return route('login');
    }

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

            // The request is already validated and authorized at this point.
            $user = $authService->registerUser($request->validated());

            // Log in the user
            Auth::login($user);

            // Clear verification session data
            $request->session()->forget(['verified_email', 'verification_email']);

            return redirect($this->redirectTo());

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
