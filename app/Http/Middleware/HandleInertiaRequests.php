<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'email' => config('settings.site.site_email'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'title' => fn () => $request->session()->get('title'),
                'duration' => fn () => $request->session()->get('duration'),
            ],
            'auth' => function () use ($request) {
                $user = $request->user();

                if ($user) {
                    $user->load(['profile', 'kyc']);
                }

                return [
                    'user' => $user,
                    'notifications' => $user ? $user->notifications()->latest()->get() : [],
                    'notification_count' => $user ? $user->unreadNotifications()->count() : 0,
                    'referral_link' => $user ? $user->referralLink() : null,
                    'referral_bonus' => config('settings.site.referral_bonus'),
                ];
            },
            'is_admin_impersonating' => $request->session()->get('admin_id'),
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
