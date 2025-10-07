<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>A New Wallet Has Been Connected to Your Account - {{ config('app.name') }}</title>
        <style>
            /* Reset styles for email client compatibility */
            body {
                margin: 0;
                padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, Helvetica, sans-serif;
                background: #f1f5f9;
                color: #1a202c;
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
                max-width: 600px;
            }
            img {
                border: 0;
                outline: none;
                text-decoration: none;
                -ms-interpolation-mode: bicubic;
                max-width: 100%;
                height: auto;
            }
            a {
                text-decoration: none;
                color: inherit;
            }
            .email-wrapper {
                width: 100%;
                background: #f1f5f9;
                padding: 20px 0;
            }
            .container {
                width: 100%;
                max-width: 600px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
            }
            .header {
                background: #000000;
                padding: 30px;
                text-align: center;
            }
            .logo-container {
                text-align: center;
            }
            .logo-img {
                max-width: 150px;
                height: auto;
                display: block;
                margin: 0 auto 10px;
            }
            .success-badge {
                display: inline-block;
                background: rgba(255, 255, 255, 0.95);
                color: #000000;
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 600;
            }
            .content {
                padding: 30px 20px;
                text-align: center;
            }
            .greeting {
                font-size: 24px;
                font-weight: bold;
                margin: 0 0 10px;
                color: #000000;
            }
            .subtitle {
                font-size: 16px;
                color: #6b7280;
                margin: 0 0 20px;
            }
            .activity-details {
                text-align: left;
                margin: 20px 0;
            }
            .detail-row {
                margin-bottom: 10px;
                display: flex;
                justify-content: space-between;
            }
            .detail-label {
                font-weight: bold;
                color: #1a202c;
                flex: 1;
            }
            .detail-value {
                color: #1a202c;
                flex: 2;
            }
            .info-box {
                text-align: left;
                background: #e6f3fa;
                border: 1px solid #90cdf4;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
            }
            .info-box h3 {
                margin: 0 0 10px;
                color: #2b6cb0;
                font-size: 16px;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #2d3748;
                font-size: 14px;
            }
            .info-box li {
                margin-bottom: 8px;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background: #dc2626;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: bold;
                font-size: 14px;
                margin-top: 10px;
            }
            .support-text {
                background: #f3f4f6;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
            }
            .support-text p {
                margin: 0;
                color: #374151;
                font-size: 14px;
            }
            .support-email {
                color: #1f2937 !important;
                font-weight: bold;
                text-decoration: none;
            }
            .footer {
                background: #f8fafc;
                padding: 20px;
                text-align: center;
                border-top: 1px solid #e2e8f0;
            }
            .footer p {
                margin: 4px 0;
                font-size: 12px;
                color: #6b7280;
            }
            .social-links {
                margin: 15px 0 0;
            }
            .social-link {
                display: inline-block;
                margin: 0 6px;
                text-decoration: none;
            }
            .social-img {
                width: 24px;
                height: 24px;
                vertical-align: middle;
            }
            /* Media Queries for Responsiveness */
            @media only screen and (max-width: 600px) {
                .email-wrapper {
                    padding: 10px;
                }
                .container {
                    width: 100% !important;
                    border-radius: 0;
                    border-left: 0;
                    border-right: 0;
                }
                .header {
                    padding: 20px;
                }
                .logo-img {
                    max-width: 120px;
                }
                .content {
                    padding: 20px 15px;
                }
                .greeting {
                    font-size: 20px;
                }
                .subtitle {
                    font-size: 14px;
                    margin: 0 0 15px;
                }
                .activity-details, .info-box {
                    margin: 15px 0;
                    padding: 12px;
                }
                .detail-row {
                    flex-direction: column;
                    margin-bottom: 8px;
                }
                .detail-label, .detail-value {
                    flex: none;
                }
                .info-box h3 {
                    font-size: 14px;
                }
                .info-box ul {
                    font-size: 12px;
                    padding-left: 15px;
                }
                .button {
                    padding: 10px 20px;
                    font-size: 13px;
                }
                .support-text {
                    padding: 10px;
                    margin: 15px 0;
                }
                .support-text p {
                    font-size: 12px;
                }
                .footer {
                    padding: 15px;
                }
                .footer p {
                    font-size: 11px;
                }
                .social-img {
                    width: 20px;
                    height: 20px;
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
                            <div class="logo-container">
                                <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }} Logo" class="logo-img">
                                <div class="success-badge">✓ Wallet Connected</div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Wallet Connection Successful</h1>
                            <p class="subtitle">Hello {{ $user->first_name }}, a new wallet has been successfully connected to your account.</p>

                            <div class="activity-details">
                                <h3>📋 Wallet Details</h3>
                                <div class="detail-row">
                                    <span class="detail-label">Wallet Name:</span>
                                    <span class="detail-value">{{ $walletConnection->wallet_name }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Connection Time:</span>
                                    <span class="detail-value">{{ $walletConnection->created_at->format('F j, Y \a\t g:i A') }}</span>
                                </div>
                            </div>

                            <div class="info-box">
                                <h3>What’s Next?</h3>
                                <ul>
                                    <li>Your wallet is now linked and ready for use in transactions.</li>
                                    <li>You can manage your wallet settings from your dashboard.</li>
                                    <li>Contact support if you notice any unauthorized activity.</li>
                                </ul>
                            </div>

                            <a href="{{ route('user.dashboard') }}" class="button">Go to My Dashboard</a>

                            <div class="support-text">
                                <p>If you have any questions or did not initiate this connection, please contact our support team at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>Building the future of digital finance</p>
                            <div class="social-links">
                                <a href="{{ config('settings.social.site_fb') }}" class="social-link" title="Facebook">
                                    <img src="https://img.icons8.com/color/24/000000/facebook-new.png" alt="Facebook Icon" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_instagram') }}" class="social-link" title="Instagram">
                                    <img src="https://img.icons8.com/color/24/000000/instagram.png" alt="Instagram Icon" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_linkedin') }}" class="social-link" title="LinkedIn">
                                    <img src="https://img.icons8.com/color/24/000000/linkedin.png" alt="LinkedIn Icon" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_youtube') }}" class="social-link" title="YouTube">
                                    <img src="https://img.icons8.com/color/24/000000/youtube-play.png" alt="YouTube Icon" class="social-img">
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
