<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\SocialLoginService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
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
            return $this->notify('error', __('auth.unsupported_provider', ['provider' => $provider]))
                ->toRoute('register');
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
            return $this->notify('error', __('auth.unsupported_provider', ['provider' => $provider]))
                ->toRoute('register');
        }

        try {

            $socialUser = Socialite::driver($provider)->stateless()->user();

            if (!$socialUser->getEmail()) {
                return $this->notify('error', __('auth.no_email'))
                    ->toRoute('register');
            }

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
            $referrerData = $referrer ? (string) $referrer->id : '';

            $user = $this->socialLoginService->findOrCreateUser($provider, $socialUser, $referrerData);

            Auth::login($user, true);

            // Clear referral session data
            Session::forget(['referral']);

            return $this->notify('success', __('Successfully logged in with :Provider.', ['provider' => ucfirst($provider)]))
                ->toRoute('secure.wallet');

        } catch (Exception $e) {
            Log::error("Social login failed for $provider: " . $e->getMessage());
            return $this->notify('error', $e->getMessage())
                ->toRoute('register');
        }
    }
}
