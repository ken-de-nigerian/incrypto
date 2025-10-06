<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialLoginService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialLoginController extends Controller
{
    protected array $supportedProviders = ['google', 'facebook', 'linkedin'];
    protected SocialLoginService $socialLoginService;

    public function __construct(SocialLoginService $socialLoginService)
    {
        $this->socialLoginService = $socialLoginService;
    }

    /**
     * Redirect to provider's authentication page.
     */
    public function redirectToProvider(string $provider)
    {
        if (!in_array($provider, $this->supportedProviders)) {
            return redirect()->route('register')->with('error', __('auth.unsupported_provider', ['provider' => $provider]));
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Handle provider callback.
     * @throws Throwable
     */
    public function handleProviderCallback(string $provider)
    {
        if (!in_array($provider, $this->supportedProviders)) {
            return redirect()->route('register')->with('error', __('auth.unsupported_provider', ['provider' => $provider]));
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            if (!$socialUser->getEmail()) {
                return redirect()->route('register')->with('error', __('auth.no_email'));
            }

            $user = $this->socialLoginService->findOrCreateUser($provider, $socialUser);

            Auth::login($user, true);

            return $this->sendLoginResponse();

        } catch (Exception $e) {
            Log::error("Social login failed for $provider: " . $e->getMessage());
            // Redirect back with a user-friendly error message from the service
            return redirect()->route('register')->with('error', $e->getMessage());
        }
    }

    /**
     * Send a successful login response based on user role.
     */
    protected function sendLoginResponse()
    {
        $user = Auth::user();

        if (Gate::allows('access-admin-dashboard', $user)) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if (Gate::allows('access-user-dashboard', $user)) {
            return redirect()->intended(route('user.dashboard'));
        }

        Auth::logout();
        return redirect()->route('login')->with('error', __('auth.no_dashboard_access'));
    }
}
