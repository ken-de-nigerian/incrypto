<?php

namespace App\Http\Controllers\Admin;

use App\Events\PasswordUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ProfileService;
use App\Services\UserSessionService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminProfileController extends Controller
{
    public function index(Request $request, UserSessionService $sessionService)
    {
        return Inertia::render('Admin/Profile', [
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

        return back()->with('success', 'Your password has been updated successfully.');
    }
}
