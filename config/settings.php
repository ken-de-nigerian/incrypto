<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Registration Settings
    |--------------------------------------------------------------------------
    |
    | This option controls whether a new user registration is enabled.
    | When disabled, the registration routes will be unavailable.
    |
    */
    'register' => [
        'enabled' => env('REGISTRATION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for login-related features including social authentication.
    |
    */
    'login' => [
        'social_enabled' => env('SOCIAL_LOGIN_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email-related features including notifications,
    | verification requirements, and the default email provider.
    |
    */
    'email_notification' => true,        // Enable/disable all email notifications
    'email_verification' => true,        // Require email verification for new accounts
    'email_provider' => 'phpmailer',     // Default mailer service (phpmailer, smtp, etc.)

    /*
    |--------------------------------------------------------------------------
    | Site Settings
    |--------------------------------------------------------------------------
    |
    | General site configuration including contact information and
    | support details displayed across the platform.
    |
    */
    'site' => [
        'site_name' => env('SITE_NAME', 'volt'),
        'site_tagline' => env('SITE_TAGLINE', 'chain'),
        'site_email' => env('SITE_EMAIL', 'support@volt-chain.org'),
        'site_phone' => env('SITE_PHONE', '+380912345678'),
        'referral_bonus' => env('REFERRAL_BONUS', '5'),
    ],

    'loan' => [
        'min_amount' => 100,
        'max_amount' => 5000000,
        'interest_rate' => 12.5, // Default base rate
        'repayment_period' => 36, // Max months
    ],
];
