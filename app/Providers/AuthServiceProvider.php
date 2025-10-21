<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('access-admin-dashboard', function (User $user) {
            return $user->role === 'admin' || session()->has('admin_id');
        });

        Gate::define('access-user-dashboard', function (User $user) {
            return $user->role === 'user' || session()->has('admin_id');
        });
    }
}
