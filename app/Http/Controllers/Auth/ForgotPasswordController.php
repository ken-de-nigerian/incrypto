<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    /**
     * Show the password-reset request form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Send a password reset link to the given user.
     */
    public function store(SendPasswordResetLinkRequest $request): RedirectResponse
    {
        $throttleKey = $this->throttleKey($request->input('email'));

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => __('Too many password reset attempts.'),
                'throttle' => $seconds,
            ]);
        }

        $status = Password::sendResetLink($request->validated());

        if ($status === Password::RESET_LINK_SENT) {
            RateLimiter::hit($throttleKey); // 60-second cooldown
            return $this->notify('success', __($status))->toBack();
        }

        return $this->notifyErrorWithValidation(
            'Validation failed',
            ['email' => __($status)]
        );
    }

    /**
     * Show the password reset form.
     */
    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $token,
        ]);
    }

    /**
     * Reset the user's password.
     * @throws AuthenticationException
     */
    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset($request->validated(), function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return $this->notifyErrorWithValidation(
                'Validation failed',
                ['email' => __($status)]
            );
        }

        $user = User::where('email', $request->email)->first();

        // Log the user in on the current device
        Auth::login($user);

        // Invalidate all other browser sessions for the user
        Auth::logoutOtherDevices($request->password);

        $notification = $this->notify('success', __($status));

        return match ($user->role) {
            'admin' => $notification->toRoute('admin.dashboard'),
            'user' => $notification->toRoute('user.dashboard'),
            default => redirect()->route('login'),
        };
    }

    /**
     * Generate the throttle key for password reset requests.
     */
    protected function throttleKey(string $email): string
    {
        return 'password-reset|' . strtolower($email) . '|' . request()->ip();
    }
}
