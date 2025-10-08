<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Reset Your Password - {{ config('app.name') }}</title>
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
                background-color: #dbeafe; /* Blue for info/action */
                color: #2563eb;
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
            .cta-section {
                margin: 32px 0 16px;
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
                background-color: #334155;
            }
            .expiry-notice {
                font-size: 13px;
                color: #64748b;
                margin-top: 16px;
            }
            .link-box {
                padding: 20px;
                margin: 32px 0;
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                text-align: left;
            }
            .link-box p {
                margin: 0 0 12px;
                font-size: 14px;
                color: #475569;
            }
            .link-box p:last-child {
                margin-bottom: 0;
            }
            .link-box .link-text {
                word-break: break-all;
                color: #1d4ed8;
                font-size: 13px;
                font-family: 'Courier New', Courier, monospace;
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
            .info-box p {
                margin: 0;
                color: #0c4a6e;
                font-size: 14px;
                line-height: 1.5;
            }
            .warning-box {
                background: #fee2e2;
                border-left: 4px solid #dc2626;
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }
            .warning-box h3 {
                margin: 0 0 12px;
                color: #b91c1c;
                font-size: 17px;
                font-weight: 700;
                display: flex;
                align-items: center;
            }
            .warning-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 12px;
            }
            .warning-box p {
                margin: 0;
                color: #b91c1c;
                font-size: 14px;
                line-height: 1.5;
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
                .button {
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                }
                .link-box, .info-box, .warning-box {
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
                            <div class="badge">Password Reset</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Reset Your Password</h1>
                            <p class="subtitle">Hello {{ $name }}, we received a request to reset the password for your account. Click the button below to continue.</p>

                            <div class="cta-section">
                                <a href="{{ $resetUrl }}" class="button">Reset My Password</a>
                                <p class="expiry-notice">This link will expire in 30 minutes.</p>
                            </div>

                            <div class="warning-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/ef4444/error.png" alt="Warning Icon">
                                    Didn't Request This?
                                </h3>
                                <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged. For added security, you can log in and change your password manually.</p>
                            </div>

                            <div class="link-box">
                                <p>If you're having trouble with the button above, copy and paste this URL into your web browser:</p>
                                <p><a href="{{ $resetUrl }}" class="link-text">{{ $resetUrl }}</a></p>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/info.png" alt="Info Icon">
                                    Our Commitment to Security
                                </h3>
                                <p>We take your account security seriously. Automated emails like this are sent to ensure that only you have the ability to make changes to your account.</p>
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
