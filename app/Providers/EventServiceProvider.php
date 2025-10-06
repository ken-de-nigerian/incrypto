<?php

namespace App\Providers;

use App\Events\AccountDeleted;
use App\Events\KycSubmitted;
use App\Events\PasswordUpdated;
use App\Listeners\NotifyAdminOfKycSubmission;
use App\Listeners\SendAccountDeletionNotification;
use App\Listeners\SendKycConfirmationToUser;
use App\Listeners\SendPasswordChangeNotification;
use App\Listeners\SendPasswordResetConfirmation;
use App\Listeners\SendWelcomeEmailNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected array $listen = [
        Registered::class => [
            SendWelcomeEmailNotification::class,
            SendPasswordResetConfirmation::class,
        ],
        PasswordUpdated::class => [
            SendPasswordChangeNotification::class,
        ],
        AccountDeleted::class => [
            SendAccountDeletionNotification::class,
        ],
        KycSubmitted::class => [
            SendKycConfirmationToUser::class,
            NotifyAdminOfKycSubmission::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
