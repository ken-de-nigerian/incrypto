<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- SEO Meta Tags -->
        <meta name="description" content="{{ config('app.name') }} - Your all-in-one trading platform. Trade cryptocurrency, forex, and stocks. Send, receive, and swap digital assets. Copy successful traders and grow your portfolio from one secure wallet." />
        <meta name="keywords" content="cryptocurrency trading, forex trading, stock trading, crypto wallet, copy trading, digital assets, bitcoin, ethereum, swap crypto, invest, {{ strtolower(config('app.name')) }}" />
        <meta name="author" content="{{ config('app.name') }}" />
        <meta name="robots" content="index, follow" />
        <meta name="language" content="English" />
        <link rel="canonical" href="{{ url()->current() }}" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:title" content="{{ config('app.name') }} - Trade Crypto, Forex & Stocks | Secure Multi-Asset Platform" />
        <meta property="og:description" content="All-in-one trading platform for crypto, forex, and stocks. Send, receive, swap assets, and copy top traders—all from one secure wallet. Start trading smarter today." />
        <meta property="og:image" content="{{ asset('images.jpeg') }}" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:site_name" content="{{ config('app.name') }}" />
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="{{ url()->current() }}" />
        <meta name="twitter:title" content="{{ config('app.name') }} - Multi-Asset Trading Platform" />
        <meta name="twitter:description" content="Trade crypto, forex & stocks. Copy top traders, swap assets instantly, and manage everything from one secure wallet. Join thousands of successful traders." />
        <meta name="twitter:image" content="{{ asset('images.jpeg') }}" />
        <meta name="twitter:site" content="@{{ config('app.name') }}" />
        <meta name="twitter:creator" content="@{{ config('app.name') }}" />

        <!-- Additional SEO -->
        <meta name="theme-color" content="#000000" />
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="format-detection" content="telephone=no" />

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}" />
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon.png') }}" />

        <script>
            (function() {
                const stored = localStorage.getItem('appearance');
                const system = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (stored === 'dark' || (!stored && system) || (stored === 'system' && system)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>

    <body class="bg-background text-foreground font-sans antialiased transition-colors duration-300">
        @inertia
    </body>
</html>
