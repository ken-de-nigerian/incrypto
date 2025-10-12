<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    protected int $maxAttempts = 5; // Max login attempts before lockout
    protected int $decayMinutes = 10; // Lockout duration in minutes

    /**
     * Render the login page
     */
    public function index(Request $request)
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Log the user in
     */
    public function login(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if a user has too many failed login attempts
        if ($this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        // Attempt login first (DO NOT invalidate session yet)
        if ($this->attemptLogin($request)) {
            // Regenerate session only if login is successful
            $request->session()->regenerate();
            $this->clearLoginAttempts($request);

            return $this->sendLoginResponse();
        }

        // Increment login attempts if failed
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Get login credentials from request.
     */
    protected function credentials(Request $request): array
    {
        return $request->only('email', 'password');
    }

    /**
     * Attempt to log in with provided credentials.
     */
    /**
     * Attempt to log in with provided credentials.
     */
    protected function attemptLogin(Request $request): bool
    {
        $credentials = $this->credentials($request);

        // Find the user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if a user exists
        if ($user) {

            // 1. Check if the account is inactive
            if ($user->status === 'inactive') {
                throw ValidationException::withMessages([
                    $this->username() => [__('Your account has been suspended. Please contact support for assistance.')],
                ])->status(403);
            }

            // 2. Check if the account is social-only
            if (!empty($user->social_login_provider) && !empty($user->social_login_id)) {
                $providerName = ucfirst($user->social_login_provider);
                throw ValidationException::withMessages([
                    $this->username() => [__("This account is linked to a social login. Please use $providerName to sign in.")],
                ]);
            }
        }

        // Proceed with authentication
        return Auth::attempt(
            $credentials,
            $request->boolean('remember')
        );
    }

    /**
     * Send a successful login response.
     */
    protected function sendLoginResponse(): RedirectResponse
    {
        if (Gate::allows('access-admin-dashboard')) {
            $redirectUrl = route('admin.dashboard');
        } elseif (Gate::allows('access-user-dashboard')) {
            $redirectUrl = route('user.dashboard');
        } else {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => __('Your account does not have access to any dashboard.')
            ]);
        }

        return redirect()->intended($redirectUrl);
    }

    /**
     * Send a failed login response.
     */
    protected function sendFailedLoginResponse(Request $request): RedirectResponse
    {
        $errorMessage = __('These credentials do not match our records.');

        // Always redirect back with errors
        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $errorMessage]);
    }

    /**
     * Get the field used for authentication.
     */
    public function username(): string
    {
        return 'email';
    }

    /**
     * Handle a lockout response when too many login attempts occur.
     *
     * @throws ValidationException
     */
    protected function sendLockoutResponse(Request $request): RedirectResponse
    {
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('Please wait a minute before retrying.'),
            'throttle' => $seconds
        ])->status(429);
    }

    /**
     * Clear failed login attempts for the user.
     */
    protected function clearLoginAttempts(Request $request): void
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    /**
     * Get the unique key for tracking login attempts.
     */
    protected function throttleKey(Request $request): string
    {
        return mb_strtolower($request->input($this->username())).'|'.$request->ip();
    }

    /**
     * Check if the user has exceeded maximum login attempts.
     */
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->throttleKey($request),
            $this->maxAttempts
        );
    }

    /**
     * Increment the login attempt count.
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        RateLimiter::hit(
            $this->throttleKey($request),
            $this->decayMinutes * 60
        );
    }
}
