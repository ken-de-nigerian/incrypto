<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Password Changed Successfully - {{ config('app.name') }}</title>
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
            .success-badge {
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
            .activity-details {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: left;
            }
            .activity-details h3 {
                margin: 0 0 16px;
                color: #1f2a44;
                font-size: 18px;
                font-weight: 600;
            }
            .detail-row {
                display: flex;
                padding: 12px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .detail-label {
                font-weight: 600;
                color: #1f2a44;
                min-width: 100px;
                font-size: 15px;
            }
            .detail-value {
                color: #1f2a44;
                font-size: 15px;
                flex: 1;
                word-break: break-word;
            }
            .security-box {
                background: #fef3c7;
                border: 1px solid #fde68a;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: left;
            }
            .security-box h3 {
                margin: 0 0 12px;
                color: #92400e;
                font-size: 17px;
                display: flex;
                align-items: center;
            }
            .security-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 8px;
                vertical-align: middle;
            }
            .security-box ul {
                margin: 0;
                padding-left: 20px;
                color: #78350f;
                font-size: 15px;
            }
            .security-box li {
                margin: 6px 0;
            }
            .warning-box {
                background: #fee2e2;
                border: 2px solid #fca5a5;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: center;
            }
            .warning-box h3 {
                margin: 0 0 12px;
                color: #991b1b;
                font-size: 18px;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .warning-box h3::before {
                content: '⚠️';
                margin-right: 8px;
            }
            .warning-box p {
                margin: 0 0 16px;
                color: #991b1b;
                font-size: 15px;
            }
            .button {
                display: inline-block;
                padding: 12px 28px;
                background: #dc2626;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 15px;
                margin: 16px 0;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background: #b91c1c;
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
                .success-badge {
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
                .activity-details {
                    padding: 16px;
                    margin: 16px 0;
                }
                .activity-details h3 {
                    font-size: 16px;
                }
                .detail-row {
                    flex-direction: column;
                    padding: 10px 0;
                    font-size: 14px;
                }
                .detail-label {
                    min-width: auto;
                    margin-bottom: 4px;
                }
                .detail-value {
                    font-size: 14px;
                }
                .security-box, .warning-box {
                    padding: 16px;
                    margin: 16px 0;
                }
                .security-box h3, .warning-box h3 {
                    font-size: 15px;
                }
                .security-box h3 img {
                    width: 20px;
                    height: 20px;
                }
                .security-box ul {
                    font-size: 14px;
                    padding-left: 15px;
                }
                .warning-box p {
                    font-size: 14px;
                }
                .button {
                    padding: 10px 24px;
                    font-size: 14px;
                }
                .support-text {
                    padding: 16px;
                    margin: 16px 0;
                }
                .support-text p {
                    font-size: 14px;
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
                                <div class="success-badge">✓ Password Changed</div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Password Changed Successfully</h1>
                            <p class="subtitle">Hello {{ $user->first_name }}, your {{ config('app.name') }} account password has been successfully updated.</p>

                            <div class="activity-details">
                                <h3>📋 Activity Details</h3>
                                <div class="detail-row">
                                    <span class="detail-label">Account:</span>
                                    <span class="detail-value">{{ $user->email }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Date & Time:</span>
                                    <span class="detail-value">{{ now()->format('F j, Y \a\t g:i A') }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">IP Address:</span>
                                    <span class="detail-value">{{ $ip }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Device:</span>
                                    <span class="detail-value">{{ $device }}</span>
                                </div>
                            </div>

                            <div class="security-box">
                                <h3>
                                    <img src="https://img.icons8.com/ios-glyphs/24/92400e/security-checked.png" alt="Security Icon">
                                    Security Recommendations
                                </h3>
                                <ul>
                                    <li>Use a strong, unique password for your account.</li>
                                    <li>Never share your password with anyone.</li>
                                    <li>Enable two-factor authentication for extra security.</li>
                                    <li>Regularly update your password every few months.</li>
                                    <li>Be cautious of phishing attempts.</li>
                                </ul>
                            </div>

                            <div class="warning-box">
                                <h3>Didn't Make This Change?</h3>
                                <p>If you did not change your password, your account may be compromised. Please secure your account immediately by resetting your password and contacting our support team.</p>
                                <a href="{{ route('password.request') }}" class="button">Secure My Account</a>
                            </div>

                            <div class="support-text">
                                <p>If you have any questions or need assistance, our support team is available 24/7 at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
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
