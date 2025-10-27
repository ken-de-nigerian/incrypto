<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    protected array $supportedProviders = ['google', 'facebook', 'linkedin'];

    /**
     * Log the user out of the application.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Log out all of the user's other browser sessions.
     * @throws AuthenticationException
     */
    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        // Delete other sessions
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        Auth::logoutOtherDevices($request->password);

        return $this->notify('success', __('All other browser sessions have been logged out.'))->toBack();
    }

    /**
     * Unlink a social provider from the user's account.
     * Note: This might be better placed in a UserProfileController.
     */
    public function unlinkSocialAccount(Request $request, string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->supportedProviders)) {
            return $this->notify('error', __('auth.unsupported_provider', ['provider' => $provider]))->toBack();
        }

        $request->user()->update([
            'social_login_provider' => null,
            'social_login_id' => null
        ]);

        return $this->notify('success', __('Successfully unlinked your :Provider account.', ['provider' => ucfirst($provider)]))->toBack();
    }

    public function exitUserSession()
    {
        try {
            $adminId = session('admin_id');

            if (!$adminId) {
                return redirect()->route('admin.dashboard');
            }

            $admin = User::find($adminId);
            Auth::guard('web')->login($admin);

            session()->forget('admin_id');

            return $this->notify('success', __('You have successfully returned to your admin session.'))->toRoute('admin.dashboard');
        } catch (Exception $e) {
            Log::error('Exit user session failed', ['error' => $e->getMessage()]);
            return $this->notify('error', __('Failed to exit user session. Please try logging in again.'))->toBack();
        }
    }
}
