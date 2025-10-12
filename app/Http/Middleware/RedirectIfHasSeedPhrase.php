<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfHasSeedPhrase
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Redirect if either completed or skipped
        if ($this->hasCompletedSeedPhraseSetup($user) || $this->hasSkippedSeedPhraseSetup($user)) {
            if ($user->role == 'user'){
                return redirect()->route('user.dashboard');
            }

            if ($user->role == 'admin'){
                return redirect()->route('admin.dashboard');
            }
        }

        return $next($request);
    }

    /**
     * @param $user
     * @return bool
     */
    protected function hasCompletedSeedPhraseSetup($user): bool
    {
        return !empty($user->profile->seed_phrase)
            && $user->profile->seed_phrase_status === 'generated';
    }

    /**
     * @param $user
     * @return bool
     */
    protected function hasSkippedSeedPhraseSetup($user): bool
    {
        return !empty($user->profile->seed_phrase_skipped_at)
            && $user->profile->seed_phrase_status === 'skipped'
            && (empty($user->profile->seed_phrase_expires_at)
                || now()->lte($user->profile->seed_phrase_expires_at));
    }
}
