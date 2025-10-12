<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>New Referral! - {{ config('app.name') }}</title>
        <style>
            /* --- EXISTING CSS STYLES (Keep them all the same) --- */
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
                background-color: #fffbeb; /* Yellow/Gold for celebration */
                color: #b45309;
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
            /* --- Referral Info Table Styling --- */
            .referral-info-table {
                width: 100%;
                max-width: 400px;
                margin: 32px auto 40px;
                text-align: left;
                border-spacing: 0;
            }
            .referral-info-table td {
                padding: 12px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .referral-info-table tr:last-child td {
                border-bottom: none;
            }
            .referral-info-label {
                font-size: 14px;
                color: #64748b;
                font-weight: 500;
                width: 50%;
            }
            .referral-info-value {
                font-size: 16px;
                color: #1f2a44;
                font-weight: 700;
                width: 50%;
                text-align: right;
            }
            /* --- End Referral Info Table --- */
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
                background-color: #0d9488; /* A nice referral green/teal */
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background-color: #0f766e;
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
                margin: 8px 0;
                color: #0c4a6e;
                font-size: 14px;
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
                            <div class="badge">New Referral Signed Up! 🎉</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Congratulations, {{ $referrerName }}!</h1>
                            <p class="subtitle">
                                Great news! One of your friends has just signed up using your referral link.
                            </p>

                            <table class="referral-info-table" role="presentation" align="center">
                                <tr>
                                    <td class="referral-info-label">Referred User:</td>
                                    <td class="referral-info-value">{{ $newUserName }}</td>
                                </tr>
                                <tr>
                                    <td class="referral-info-label">Status:</td>
                                    <td class="referral-info-value">Account Created</td>
                                </tr>
                            </table>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/checklist.png" alt="Checklist Icon">
                                    What Happens Next?
                                </h3>
                                <p>You will receive your **referral bonus** once {{ $newUserName }} completes their first qualifying deposit. We'll notify you as soon as that happens!</p>
                                <p>Keep sharing your link to earn more.</p>
                            </div>

                            <div class="button-container">
                                <p class="cta-title">Check your progress and share your link!</p>
                                <a href="{{ route('user.dashboard') }}" class="button">View Your Referrals Dashboard</a>
                            </div>

                        </div>

                        <div class="footer">
                            <p>Thanks,<br>The {{ config('app.name') }} Team</p>
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
