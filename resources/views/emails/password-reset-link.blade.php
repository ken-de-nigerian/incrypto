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
                background: #f4f7fa;
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
                background: #f4f7fa;
                padding: 24px 16px;
            }
            .container {
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }
            .header {
                background: #1f2a44;
                padding: 32px 24px;
                text-align: center;
            }
            .logo-container {
                text-align: center;
            }
            .logo-img {
                max-width: 140px;
                margin: 0 auto 12px;
            }
            .welcome-badge {
                display: inline-block;
                background: #ffffff;
                color: #1f2a44;
                padding: 8px 20px;
                border-radius: 9999px;
                font-size: 14px;
                font-weight: 600;
                border: 1px solid #e2e8f0;
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
                margin: 0 auto 24px;
            }
            .cta-section {
                margin: 32px 0;
                text-align: center;
            }
            .button {
                display: inline-block;
                padding: 14px 32px;
                background: #1f2a44;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background: #2d3748;
            }
            .alternative-link {
                margin: 24px 0;
                padding: 20px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                text-align: left;
            }
            .alternative-link p {
                margin: 0 0 12px;
                font-size: 14px;
                color: #64748b;
            }
            .link-text {
                word-break: break-all;
                color: #3b82f6;
                font-size: 13px;
                font-family: 'Courier New', monospace;
            }
            .info-box {
                background: #fef3c7;
                border: 1px solid #fde68a;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 12px;
                color: #92400e;
                font-size: 17px;
                display: flex;
                align-items: center;
            }
            .info-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 8px;
                vertical-align: middle;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #78350f;
                font-size: 15px;
            }
            .info-box li {
                margin: 6px 0;
            }
            .warning-box {
                background: #fee2e2;
                border: 1px solid #fecaca;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: left;
            }
            .warning-box p {
                margin: 0;
                color: #991b1b;
                font-size: 15px;
            }
            .support-text {
                background: #f3f4f6;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
            }
            .support-text p {
                margin: 0;
                color: #1f2a44;
                font-size: 15px;
            }
            .support-email {
                color: #1f2a44 !important;
                font-weight: 600;
                text-decoration: underline;
            }
            .support-email:hover {
                color: #dc2626 !important;
            }
            .expiry-notice {
                font-size: 14px;
                color: #64748b;
                margin: 16px 0;
                font-style: italic;
            }
            .footer {
                background: #f8fafc;
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
                vertical-align: middle;
                opacity: 0.8;
                transition: opacity 0.2s ease;
            }
            .social-img:hover {
                opacity: 1;
            }
            /* Media Queries for Responsiveness */
            @media only screen and (max-width: 640px) {
                .email-wrapper {
                    padding: 16px 8px;
                }
                .container {
                    border-radius: 0;
                    border-left: 0;
                    border-right: 0;
                }
                .header {
                    padding: 24px 16px;
                }
                .logo-img {
                    max-width: 120px;
                }
                .welcome-badge {
                    padding: 6px 16px;
                    font-size: 13px;
                }
                .content {
                    padding: 24px 16px;
                }
                .greeting {
                    font-size: 22px;
                }
                .subtitle {
                    font-size: 14px;
                    margin: 0 0 16px;
                }
                .cta-section {
                    margin: 24px 0;
                }
                .button {
                    padding: 12px 24px;
                    font-size: 15px;
                    width: auto;
                    max-width: 100%;
                }
                .alternative-link {
                    padding: 16px;
                    margin: 16px 0;
                }
                .alternative-link p {
                    font-size: 13px;
                }
                .link-text {
                    font-size: 12px;
                }
                .info-box, .warning-box {
                    padding: 16px;
                    margin: 16px 0;
                }
                .info-box h3 {
                    font-size: 15px;
                }
                .info-box h3 img {
                    width: 20px;
                    height: 20px;
                }
                .info-box ul {
                    font-size: 14px;
                    padding-left: 15px;
                }
                .warning-box p {
                    font-size: 14px;
                }
                .support-text {
                    padding: 16px;
                    margin: 16px 0;
                }
                .support-text p {
                    font-size: 14px;
                }
                .expiry-notice {
                    font-size: 13px;
                }
                .footer {
                    padding: 16px;
                }
                .footer p {
                    font-size: 12px;
                }
                .social-img {
                    width: 24px;
                    height: 24px;
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
                                    <img src="https://img.icons8.com/ios-glyphs/24/92400e/info.png" alt="Info Icon">
                                    Why am I receiving this?
                                </h3>
                                <ul>
                                    <li>Someone (hopefully you) requested a password reset.</li>
                                    <li>This is an automated security measure.</li>
                                    <li>Your account security is our priority.</li>
                                </ul>
                            </div>

                            <div class="warning-box">
                                <p><strong>Important:</strong> If you didn’t request this password reset, please ignore this email. Your password will remain unchanged. For additional security, consider changing your password and enabling two-factor authentication.</p>
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
                                    <img src="https://img.icons8.com/color/28/000000/facebook-new.png" alt="Facebook" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_instagram') }}" class="social-link" title="Instagram">
                                    <img src="https://img.icons8.com/color/28/000000/instagram.png" alt="Instagram" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_linkedin') }}" class="social-link" title="LinkedIn">
                                    <img src="https://img.icons8.com/color/28/000000/linkedin.png" alt="LinkedIn" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_youtube') }}" class="social-link" title="YouTube">
                                    <img src="https://img.icons8.com/color/28/000000/youtube-play.png" alt="YouTube" class="social-img">
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
