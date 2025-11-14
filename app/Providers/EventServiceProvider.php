<?php

namespace App\Providers;

use App\Events\AccountDeleted;
use App\Events\TradeClosed;
use App\Events\TradeExecuted;
use App\Events\TradingAccountDebited;
use App\Events\TradingAccountFunded;
use App\Events\BalanceAdjusted;
use App\Events\CryptoReceived;
use App\Events\CryptoSent;
use App\Events\DatabaseAndEmailNotificationDispatchedEvent;
use App\Events\DatabaseNotificationDispatchedEvent;
use App\Events\EmailNotificationDispatchedEvent;
use App\Events\KycSubmitted;
use App\Events\PasswordUpdated;
use App\Events\UserReferred;
use App\Events\WalletConnected;
use App\Events\WalletStatusUpdated;
use App\Listeners\DatabaseAndEmailNotificationListener;
use App\Listeners\DatabaseNotificationListener;
use App\Listeners\EmailNotificationListener;
use App\Listeners\NotifyAdminOfKycSubmission;
use App\Listeners\SendAccountDeletionNotification;
use App\Listeners\SendAdminWalletNotification;
use App\Listeners\SendBalanceAdjustedNotification;
use App\Listeners\SendCryptoReceivedNotifications;
use App\Listeners\SendCryptoSentNotifications;
use App\Listeners\SendDebitedSuccessfulNotification;
use App\Listeners\SendTradeClosedNotification;
use App\Listeners\SendTradeExecutedNotification;
use App\Listeners\SendFundedSuccessfulNotification;
use App\Listeners\SendKycConfirmationToUser;
use App\Listeners\SendPasswordChangeNotification;
use App\Listeners\SendPasswordResetConfirmation;
use App\Listeners\SendReferralNotification;
use App\Listeners\SendUserWalletNotification;
use App\Listeners\SendWalletStatusUpdatedNotification;
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
        UserReferred::class => [
            SendReferralNotification::class,
        ],
        WalletStatusUpdated::class => [
            SendWalletStatusUpdatedNotification::class,
        ],
        BalanceAdjusted::class => [
            SendBalanceAdjustedNotification::class,
        ],
        TradingAccountFunded::class => [
            SendFundedSuccessfulNotification::class,
        ],
        TradingAccountDebited::class => [
            SendDebitedSuccessfulNotification::class,
        ],
        TradeExecuted::class => [
            SendTradeExecutedNotification::class,
        ],
        TradeClosed::class => [
            SendTradeClosedNotification::class
        ],
        DatabaseAndEmailNotificationDispatchedEvent::class => [
            DatabaseAndEmailNotificationListener::class,
        ],
        DatabaseNotificationDispatchedEvent::class => [
            DatabaseNotificationListener::class,
        ],
        EmailNotificationDispatchedEvent::class => [
            EmailNotificationListener::class,
        ],
    ];
}
