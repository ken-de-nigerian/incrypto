<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Verify Your Account - {{ config('app.name') }}</title>
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
            .verification-code {
                font-size: 32px;
                font-weight: bold;
                color: #000000;
                text-align: center;
                padding: 16px;
                background: #f3f4f6;
                border-radius: 8px;
                letter-spacing: 4px;
                margin: 20px 0;
                font-family: 'Courier New', monospace;
            }
            .cta-section {
                margin: 30px 0;
                text-align: center;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background: #000000;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: bold;
                font-size: 16px;
            }
            .info-box {
                background: #f0f9ff;
                border: 1px solid #e0f2fe;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 10px;
                color: #0369a1;
                font-size: 16px;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #374151;
                font-size: 14px;
            }
            .info-box li {
                margin: 5px 0;
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
                    line-height: 1.4;
                    padding: 0 10px;
                    text-align: center;
                    display: block;
                    width: 100%;
                    box-sizing: border-box;
                }
                .verification-code {
                    font-size: 24px;
                    padding: 12px;
                    margin: 15px 0;
                    letter-spacing: 3px;
                }
                .button {
                    padding: 10px 20px;
                    font-size: 14px;
                    display: block;
                    width: auto;
                    max-width: 200px;
                    margin: 0 auto;
                }
                .info-box {
                    padding: 12px;
                    margin: 15px 0;
                }
                .info-box h3 {
                    font-size: 14px;
                }
                .info-box ul {
                    font-size: 12px;
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
                                <div class="welcome-badge">Account Verification</div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Verify Your Account</h1>

                            <p class="subtitle">Welcome to {{ config('app.name') }}! Use the verification code below to complete your account setup.</p>

                            <div class="verification-code">{{ $token }}</div>

                            <p>Enter this code on the verification page to activate your account. This code will expire in 10 minutes.</p>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/ios-glyphs/24/0369a1/lock-2.png" alt="Security Icon" style="vertical-align: -6px; margin-right: 8px; width:24px; height:24px;">Security Tips:
                                </h3>
                                <ul>
                                    <li>Never share this code with anyone</li>
                                    <li>Our team will never ask for your verification code</li>
                                    <li>Complete your verification in a secure environment</li>
                                    <li>Delete this email after successful verification</li>
                                </ul>
                            </div>

                            <div class="support-text">
                                <p>If you didn't attempt to create an account with {{ config('app.name') }}, please ignore this email or contact our support team at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
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
