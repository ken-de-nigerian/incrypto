<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <meta name="description" content="Buy, sell & trade Bitcoin, Ethereum and other cryptocurrencies with InCrypto. Secure crypto exchange platform with advanced trading tools." />
        <meta name="author" content="InCrypto" />

        <meta property="og:title" content="InCrypto - Cryptocurrency Exchange & Trading Platform" />
        <meta property="og:description" content="Buy, sell & trade Bitcoin, Ethereum and other cryptocurrencies with InCrypto. Secure crypto exchange platform with advanced trading tools." />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="https://lovable.dev/opengraph-image-p98pqg.png" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@lovable_dev" />
        <meta name="twitter:image" content="https://lovable.dev/opengraph-image-p98pqg.png" />

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>

    <body class="bg-background text-foreground font-sans">
        @inertia
    </body>
</html>
