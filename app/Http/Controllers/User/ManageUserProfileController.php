<?php

namespace App\Http\Controllers\User;

use App\Events\AccountDeleted;
use App\Events\PasswordUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateWalletStatusRequest;
use App\Services\ProfileService;
use App\Services\UserSessionService;
use App\Services\WalletService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use JsonException;
use Throwable;

class ManageUserProfileController extends Controller
{
    public function index(Request $request, UserSessionService $sessionService)
    {
        return Inertia::render('User/Profile', [
            'activeTab' => $request->query('tab', 'profile'),
            'activeSessions' => fn () => $sessionService->getActiveSessions($request),
            'connectedAccounts' => $request->user()->social_login_provider,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function updateProfile(UpdateProfileRequest $request, ProfileService $profileService)
    {
        $profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );

        return $this->notify('success', 'Your personal details have been updated successfully.')->toBack();
    }

    /**
     * @throws AuthenticationException
     */
    public function resetPassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        // Dispatch the event with the user object
        event(new PasswordUpdated($user));

        // Invalidate all other browser sessions for the user
        Auth::logoutOtherDevices($request->password);

        return $this->notify('success', 'Your password has been updated successfully.')->toBack();
    }

    /**
     * @throws JsonException
     */
    public function updateWalletStatus(UpdateWalletStatusRequest $request, WalletService $walletService)
    {
        $walletService->updateWalletStatus(
            $request->user(),
            $request->validated(),
        );

        return $this->notify('success', 'Wallet status updated successfully.')->toBack();
    }

    public function destroy(DeleteAccountRequest $request)
    {
        $user = $request->user();

        // Dispatch the event *before* logging out and deleting
        event(new AccountDeleted($user));

        Auth::logout();
        $user->delete();

        return $this->notify('success', 'Your account has been deleted.')->toRoute('login');
    }
}
