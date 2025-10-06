<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Reset Your Password - {{ config('app.name') }}</title>
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
            .welcome-badge {
                display: inline-block;
                background: rgba(255, 255, 255, 0.9);
                color: #1a202c;
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
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
            .cta-section {
                margin: 30px 0;
                text-align: center;
            }
            .button {
                display: inline-block;
                padding: 14px 32px;
                background: #000000;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: bold;
                font-size: 16px;
                transition: background 0.3s ease;
            }
            .button:hover {
                background: #1f2937;
            }
            .alternative-link {
                margin-top: 20px;
                padding: 15px;
                background: #f9fafb;
                border-radius: 8px;
                text-align: left;
            }
            .alternative-link p {
                margin: 0 0 10px;
                font-size: 13px;
                color: #6b7280;
            }
            .link-text {
                word-break: break-all;
                color: #3b82f6;
                font-size: 12px;
                font-family: 'Courier New', monospace;
            }
            .info-box {
                background: #fef3c7;
                border: 1px solid #fde68a;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 10px;
                color: #92400e;
                font-size: 16px;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #78350f;
                font-size: 14px;
            }
            .info-box li {
                margin: 5px 0;
            }
            .warning-box {
                background: #fee2e2;
                border: 1px solid #fecaca;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                text-align: left;
            }
            .warning-box p {
                margin: 0;
                color: #991b1b;
                font-size: 14px;
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
            .expiry-notice {
                font-size: 14px;
                color: #6b7280;
                margin: 15px 0;
                font-style: italic;
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
                .button {
                    padding: 12px 24px;
                    font-size: 14px;
                    display: block;
                    width: auto;
                    max-width: 250px;
                    margin: 0 auto;
                }
                .alternative-link {
                    padding: 12px;
                    margin: 15px 0;
                }
                .link-text {
                    font-size: 11px;
                }
                .info-box, .warning-box {
                    padding: 12px;
                    margin: 15px 0;
                }
                .info-box h3 {
                    font-size: 14px;
                }
                .info-box ul {
                    font-size: 12px;
                }
                .warning-box p {
                    font-size: 12px;
                }
                .support-text {
                    padding: 10px;
                    margin: 15px 0;
                }
                .support-text p {
                    font-size: 12px;
                }
                .expiry-notice {
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
                                <div class="welcome-badge">Password Reset</div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Hello, {{ $name }}!</h1>

                            <p class="subtitle">We received a request to reset your password for your {{ config('app.name') }} account.</p>

                            <div class="cta-section">
                                <a href="{{ $resetUrl }}" class="button">Reset My Password</a>
                            </div>

                            <p class="expiry-notice">This link will expire in 30 minutes for security reasons.</p>

                            <div class="alternative-link">
                                <p><strong>Having trouble with the button?</strong> Copy and paste this URL into your browser:</p>
                                <p class="link-text">{{ $resetUrl }}</p>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/ios-glyphs/24/92400e/info.png" alt="Info Icon" style="vertical-align: -6px; margin-right: 8px; width:24px; height:24px;">Why am I receiving this?
                                </h3>
                                <ul>
                                    <li>Someone (hopefully you) requested a password reset</li>
                                    <li>This is an automated security measure</li>
                                    <li>Your account security is our priority</li>
                                </ul>
                            </div>

                            <div class="warning-box">
                                <p><strong>⚠️ Important:</strong> If you didn't request this password reset, please ignore this email. Your password will remain unchanged. For additional security, consider changing your password and enabling two-factor authentication.</p>
                            </div>

                            <div class="support-text">
                                <p>Need help? Our support team is here for you. Contact us at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>Building the future of digital finance</p>
                            <div class="social-links">
                                <a href="{{ config('settings.social.site_fb') }}" class="social-link" title="Facebook">
                                    <img src="https://img.icons8.com/color/24/000000/facebook-new.png" alt="Facebook" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_instagram') }}" class="social-link" title="Instagram">
                                    <img src="https://img.icons8.com/color/24/000000/instagram.png" alt="Instagram" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_linkedin') }}" class="social-link" title="LinkedIn">
                                    <img src="https://img.icons8.com/color/24/000000/linkedin.png" alt="LinkedIn" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_youtube') }}" class="social-link" title="YouTube">
                                    <img src="https://img.icons8.com/color/24/000000/youtube-play.png" alt="YouTube" class="social-img">
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
