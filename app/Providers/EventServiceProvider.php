<?php

namespace App\Providers;

use App\Events\AccountDeleted;
use App\Events\AccountFunded;
use App\Events\CryptoReceived;
use App\Events\CryptoSent;
use App\Events\KycSubmitted;
use App\Events\PasswordUpdated;
use App\Events\WalletConnected;
use App\Listeners\NotifyAdminOfKycSubmission;
use App\Listeners\SendAccountDeletionNotification;
use App\Listeners\SendAdminWalletNotification;
use App\Listeners\SendCryptoReceivedNotifications;
use App\Listeners\SendCryptoSentNotifications;
use App\Listeners\SendKycConfirmationToUser;
use App\Listeners\SendPasswordChangeNotification;
use App\Listeners\SendPasswordResetConfirmation;
use App\Listeners\SendUserWalletNotification;
use App\Listeners\SendWelcomeEmailNotification;
use App\Mail\AccountFundedConfirmation;
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
        WalletConnected::class => [
            SendUserWalletNotification::class,
            SendAdminWalletNotification::class,
        ],
        CryptoSent::class => [
            SendCryptoSentNotifications::class,
        ],
        CryptoReceived::class => [
            SendCryptoReceivedNotifications::class,
        ],
        AccountFunded::class => [
            AccountFundedConfirmation::class,
        ]
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
