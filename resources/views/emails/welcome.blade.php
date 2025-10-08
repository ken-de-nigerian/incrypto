<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Welcome to {{ config('app.name') }}</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, Helvetica, sans-serif;
                background-color: #f4f7fa;
                color: #1f2a44;
                line-height: 1.6;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                width: 100% !important;
                min-width: 100%;
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table {
                border-collapse: collapse;
                width: 100%;
                max-width: 640px;
                margin: 0 auto;
            }
            img {
                border: 0;
                outline: none;
                text-decoration: none;
                max-width: 100%;
                height: auto;
                display: block;
            }
            a {
                text-decoration: none;
                color: inherit;
            }
            .email-wrapper {
                width: 100%;
                background-color: #f4f7fa;
                padding: 24px 16px;
            }
            .container {
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }
            .header {
                background-color: #1f2a44;
                padding: 32px 24px;
                text-align: center;
            }
            .logo-img {
                max-width: 140px;
                margin: 0 auto 16px;
            }
            .badge {
                display: inline-block;
                background-color: #dcfce7; /* Green for success */
                color: #166534;
                padding: 8px 20px;
                border-radius: 9999px;
                font-size: 14px;
                font-weight: 700;
            }
            .content {
                padding: 32px 24px;
                text-align: center;
            }
            .greeting {
                font-size: 26px;
                font-weight: 700;
                margin: 0 0 12px;
                color: #1f2a44;
            }
            .subtitle {
                font-size: 16px;
                color: #64748b;
                max-width: 90%;
                margin: 0 auto 32px;
            }
            /* --- New Feature Grid Styling --- */
            .feature-table {
                width: 100%;
                border-spacing: 0;
                margin: 32px 0;
            }
            .feature-table td {
                padding: 8px;
            }
            .feature-card {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                text-align: center;
                height: 100%;
                box-sizing: border-box;
            }
            .feature-icon {
                width: 40px;
                height: 40px;
                margin: 0 auto 16px;
            }
            .feature-title {
                font-size: 16px;
                font-weight: 600;
                color: #1f2a44;
                margin: 0 0 4px;
            }
            .feature-desc {
                font-size: 14px;
                color: #64748b;
                line-height: 1.5;
            }
            /* --- End Feature Grid --- */
            .button-container {
                margin: 32px 0;
                text-align: center;
            }
            .button-container .cta-title {
                font-size: 18px;
                font-weight: 600;
                color: #1f2a44;
                margin: 0 0 16px;
            }
            .button {
                display: inline-block;
                padding: 14px 32px;
                background-color: #1f2a44;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background-color: #334155;
            }
            .info-box {
                background: #f0f9ff;
                border-left: 4px solid #0369a1;
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 12px;
                color: #0369a1;
                font-size: 17px;
                display: flex;
                align-items: center;
            }
            .info-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 12px;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #0c4a6e;
                font-size: 14px;
            }
            .info-box li {
                margin: 6px 0;
                line-height: 1.5;
            }
            .support-text {
                text-align: center;
                padding-top: 24px;
                border-top: 1px solid #e2e8f0;
                margin-top: 32px;
            }
            .support-text p {
                margin: 0;
                color: #64748b;
                font-size: 14px;
            }
            .support-email {
                color: #1f2a44 !important;
                font-weight: 600;
                text-decoration: underline;
            }
            .support-email:hover {
                color: #0369a1 !important;
            }
            .footer {
                background-color: #f8fafc;
                padding: 24px;
                text-align: center;
                border-top: 1px solid #e2e8f0;
            }
            .footer p {
                margin: 6px 0;
                font-size: 13px;
                color: #64748b;
            }
            .social-links {
                margin: 16px 0 0;
            }
            .social-link {
                display: inline-block;
                margin: 0 8px;
            }
            .social-img {
                width: 28px;
                height: 28px;
                opacity: 0.8;
                transition: opacity 0.2s ease;
            }
            .social-img:hover {
                opacity: 1;
            }

            @media only screen and (max-width: 640px) {
                .email-wrapper { padding: 0; }
                .container { border-radius: 0; border: 0; }
                .content { padding: 24px 16px; }
                .greeting { font-size: 22px; }
                .subtitle { font-size: 15px; }
                .feature-table, .feature-table tbody, .feature-table tr, .feature-table td {
                    display: block;
                    width: 100% !important;
                    box-sizing: border-box;
                    padding: 0;
                }
                .feature-card {
                    margin-bottom: 16px;
                }
                .button {
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                }
                .info-box {
                    padding: 16px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-wrapper">
            <table class="container" align="center" role="presentation">
                <tr>
                    <td>
                        <div class="header">
                            <a href="{{ config('app.url') }}" title="{{ config('app.name') }}">
                                <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }} Logo" class="logo-img">
                            </a>
                            <div class="badge">Welcome Aboard!</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Welcome to {{ config('app.name') }}!</h1>
                            <p class="subtitle">Hi {{ $user->first_name }}, your secure crypto wallet is ready. Here's a quick look at what you can do.</p>

                            <table class="feature-table" role="presentation">
                                <tr>
                                    <td valign="top">
                                        <div class="feature-card">
                                            <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/lock.png" alt="Security" class="feature-icon">
                                            <div class="feature-title">Bank-Grade Security</div>
                                            <div class="feature-desc">Your assets are protected with advanced encryption and multi-layer security.</div>
                                        </div>
                                    </td>
                                    <td valign="top">
                                        <div class="feature-card">
                                            <img src="https://img.icons8.com/material-rounded/48/0369a1/swap.png" alt="Transfers" class="feature-icon">
                                            <div class="feature-title">Instant Swaps</div>
                                            <div class="feature-desc">Send, receive, and swap crypto instantly with minimal transaction fees.</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top">
                                        <div class="feature-card">
                                            <img src="https://img.icons8.com/material-rounded/48/0369a1/bullish.png" alt="Analytics" class="feature-icon">
                                            <div class="feature-title">Real-Time Analytics</div>
                                            <div class="feature-desc">Track your portfolio performance with live market data and insights.</div>
                                        </div>
                                    </td>
                                    <td valign="top">
                                        <div class="feature-card">
                                            <img src="https://img.icons8.com/material-rounded/48/0369a1/coins.png" alt="Multi-Currency" class="feature-icon">
                                            <div class="feature-title">Multi-Currency Support</div>
                                            <div class="feature-desc">Manage Bitcoin, Ethereum, and 100+ other cryptocurrencies in one place.</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div class="button-container">
                                <p class="cta-title">Ready to get started?</p>
                                <a href="{{ route('user.dashboard') }}" class="button">Go to Your Dashboard</a>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/checklist.png" alt="Checklist Icon">
                                    Your Security Checklist
                                </h3>
                                <ul>
                                    <li><strong>Enable 2FA:</strong> Add an extra layer of security to your account right away.</li>
                                    <li><strong>Secure Your Recovery Phrase:</strong> Store it offline where only you can find it.</li>
                                    <li><strong>Stay Alert:</strong> We will never ask for your password or recovery phrase.</li>
                                </ul>
                            </div>

                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <div class="social-links">
                                <a href="{{ config('settings.social.site_fb') }}" class="social-link" title="Facebook">
                                    <img src="https://img.icons8.com/fluency/48/facebook-new.png" alt="Facebook" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_instagram') }}" class="social-link" title="Instagram">
                                    <img src="https://img.icons8.com/fluency/48/instagram-new.png" alt="Instagram" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_linkedin') }}" class="social-link" title="LinkedIn">
                                    <img src="https://img.icons8.com/fluency/48/linkedin.png" alt="LinkedIn" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_youtube') }}" class="social-link" title="YouTube">
                                    <img src="https://img.icons8.com/fluency/48/youtube-play.png" alt="YouTube" class="social-img">
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
