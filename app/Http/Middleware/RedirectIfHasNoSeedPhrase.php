<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfHasNoSeedPhrase
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

        if (!$this->hasCompletedSeedPhraseSetup($user) && !$this->hasSkippedSeedPhraseSetup($user)) {
            return redirect()->route('secure.wallet');
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
