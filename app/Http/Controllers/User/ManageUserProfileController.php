<?php

namespace App\Http\Controllers\User;

use App\Events\AccountDeleted;
use App\Events\PasswordUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateTradingStatusRequest;
use App\Services\ProfileService;
use App\Services\UserSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

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

    public function updateProfile(UpdateProfileRequest $request, ProfileService $profileService)
    {
        $profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );
        return back()->with('success', 'Your personal details have been updated successfully.');
    }

    public function resetPassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Dispatch the event with the user object
        event(new PasswordUpdated($user));

        return back()->with('success', 'Your password has been updated successfully.');
    }

    public function updateTradingStatus(UpdateTradingStatusRequest $request)
    {
        $user = $request->user();

        $user->profile->update([
            'trading_status' => $request->status
        ]);

        return back();
    }

    public function destroy(DeleteAccountRequest $request)
    {
        $user = $request->user();

        // Dispatch the event *before* logging out and deleting
        event(new AccountDeleted($user));

        Auth::logout();
        $user->delete();

        return redirect()->back()->with('success', 'Your account has been deleted.');
    }
}
