<?php

use App\Http\Middleware\HandleAdminImpersonation;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectAuthenticated;
use App\Http\Middleware\RedirectIfHasNoSeedPhrase;
use App\Http\Middleware\RedirectIfHasSeedPhrase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AuthenticateSession::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'redirect.authenticated' => RedirectAuthenticated::class,
            'no.seedphrase' => RedirectIfHasNoSeedPhrase::class,
            'has.seedphrase' => RedirectIfHasSeedPhrase::class,
            'is.admin.impersonating' => HandleAdminImpersonation::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
