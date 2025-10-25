<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Swap Alert - {{ config('app.name') }}</title>
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
                margin: 0 auto;
            }
            .content {
                padding: 32px 24px;
            }
            .greeting {
                font-size: 26px;
                font-weight: 700;
                margin: 0 0 12px;
                color: #1f2a44;
                text-align: center;
            }
            .subtitle {
                font-size: 16px;
                color: #64748b;
                text-align: center;
                max-width: 90%;
                margin: 0 auto 32px;
            }

            /* --- Swap Details Styling --- */
            .swap-container {
                margin: 24px 0;
            }
            .swap-flow-table {
                width: 100%;
                border-spacing: 0;
            }
            .token-box {
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 20px;
                text-align: center;
                background-color: #f8fafc;
            }
            .token-icon {
                width: 40px;
                height: 40px;
                margin: 0 auto 12px;
            }
            .token-amount {
                font-size: 22px;
                font-weight: 700;
                color: #1f2a44;
                margin: 0;
                line-height: 1.2;
                word-break: break-all;
            }
            .token-label {
                font-size: 14px;
                color: #64748b;
                margin: 4px 0 0;
            }
            .swap-arrow-cell {
                vertical-align: middle;
                text-align: center;
                width: 50px;
                padding: 0 8px;
            }
            .swap-arrow-icon {
                width: 32px;
                height: 32px;
            }
            .swap-metadata {
                border-top: 1px solid #e2e8f0;
                margin-top: 24px;
                padding-top: 20px;
            }
            .swap-metadata p {
                margin: 6px 0;
                color: #475569;
                font-size: 14px;
                text-align: center;
            }
            .swap-metadata strong {
                color: #1f2a44;
            }
            /* --- End Swap Details Styling --- */

            .cta-section {
                margin: 32px 0;
                text-align: center;
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
                background-color: #2d3748;
            }
            .support-text {
                text-align: center;
                padding: 24px 0 0;
                border-top: 1px solid #e2e8f0;
            }
            .support-text p {
                margin: 0 0 8px;
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
                .email-wrapper {
                    padding: 0;
                }
                .container {
                    border-radius: 0;
                    border-left: 0;
                    border-right: 0;
                }
                .content {
                    padding: 24px 16px;
                }
                .greeting {
                    font-size: 22px;
                }
                .subtitle {
                    font-size: 15px;
                }

                /* --- Responsive Swap Details: Keep horizontal layout but shrink elements --- */
                .token-box {
                    padding: 12px 8px; /* Reduced padding */
                }
                .token-icon {
                    width: 32px; /* Smaller icon */
                    height: 32px;
                    margin: 0 auto 8px;
                }
                .token-amount {
                    font-size: 16px; /* Smaller font for amount */
                }
                .token-label {
                    font-size: 11px; /* Smaller font for label */
                }
                .swap-arrow-cell {
                    padding: 0 4px; /* Tighter padding around arrow */
                }
                .swap-arrow-icon {
                    width: 24px; /* Smaller arrow */
                    height: 24px;
                }
                /* --- End Responsive Swap --- */

                .button {
                    padding: 12px 24px;
                    font-size: 15px;
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                }
                .footer p {
                    font-size: 12px;
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
                        </div>

                        <div class="content">
                            <h1 class="greeting">New Swap Alert</h1>
                            <p class="subtitle">A new cryptocurrency swap has just been completed on your platform. Here are the details.</p>

                            <div class="swap-container">
                                <table class="swap-flow-table" role="presentation">
                                    <tr>
                                        <td style="width: 45%;">
                                            <div class="token-box">
                                                <p class="token-amount">{{ $from_amount }}</p>
                                                <p class="token-label">Sent {{ $swap->from_token }}</p>
                                            </div>
                                        </td>

                                        <td class="swap-arrow-cell">
                                            <img src="https://img.icons8.com/ios-glyphs/30/1f2a44/long-arrow-right.png" alt="to" class="swap-arrow-icon">
                                        </td>

                                        <td style="width: 45%;">
                                            <div class="token-box">
                                                <p class="token-amount">{{ $to_amount }}</p>
                                                <p class="token-label">Received {{ $swap->to_token }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <div class="swap-metadata">
                                    <p><strong style="margin-right: 5px;">User:</strong> {{ $swap->user->first_name }} {{ $swap->user->last_name }} ({{ $swap->user->email }})</p>
                                    <p><strong style="margin-right: 5px;">Date:</strong> {{ $swap->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>

                            <div class="cta-section">
                                <a href="{{ route('admin.transaction.index') }}" class="button">View Full Swap Details</a>
                            </div>

                            <div class="support-text">
                                <p>If you have any questions, please contact our support team.</p>
                                <p><a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a></p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>Building the future of digital finance</p>
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
