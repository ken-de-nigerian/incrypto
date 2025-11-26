<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Registration Settings
    |--------------------------------------------------------------------------
    |
    | This option controls whether new user registration is enabled.
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
        'site_email' => env('SITE_EMAIL', 'nwanerick14489@gmail.com'),
        'referral_bonus' => env('REFERRAL_BONUS', '5'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media Settings
    |--------------------------------------------------------------------------
    |
    | Social media profile links displayed in emails, footers,
    | and throughout the platform interface.
    |
    */
    'social' => [
        'site_fb' => env('SOCIAL_FACEBOOK', 'https://facebook.com/cryptoexample'),
        'site_instagram' => env('SOCIAL_INSTAGRAM', 'https://instagram.com/cryptoexample'),
        'site_linkedin' => env('SOCIAL_LINKEDIN', 'https://linkedin.com/company/cryptoexample'),
        'site_youtube' => env('SOCIAL_YOUTUBE', 'https://youtube.com/channel/cryptoexample'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default fiat currency configuration for displaying balances,
    | prices, and conducting financial transactions.
    |
    */
    'currency' => [
        'code' => 'USD',                 // ISO 4217 currency code
        'symbol' => '$',                 // Currency symbol for display
        'precision' => 2,                // Decimal places for amounts
        'network_fee' => 0.042,
        'charge_network_fee' => false
    ],

    /*
    |--------------------------------------------------------------------------
    | Cryptocurrency API Keys
    |--------------------------------------------------------------------------
    |
    | API credentials for third-party cryptocurrency data providers.
    | These services provide real-time prices, market data, and analytics.
    |
    */
    'cryptocompare' => [
        'key' => env('CRYPTOCOMPARE_API_KEY'),  // CryptoCompare API key
    ],

    'coinmarketcap' => [
        'key' => env('COINMARKETCAP_API_KEY'),  // CoinMarketCap API key
    ],

    'coingecko' => [
        'key' => env('COINGECKO_API_KEY'),      // CoinGecko API key (optional for free tier)
    ],
];
