@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Account Deleted Successfully - {{ config('app.name') }}</title>
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
                color: #0c4a6e;
                font-size: 14px;
                margin: 0 0 10px;
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
                margin: 0 0 16px;
                color: #b91c1c;
                font-size: 14px;
            }
            .button-container {
                margin-top: 16px;
                text-align: left;
            }
            .button {
                display: inline-block;
                padding: 12px 28px;
                background-color: #dc2626;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 15px;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background-color: #b91c1c;
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
                .info-box, .warning-box {
                    padding: 16px;
                }
                .button {
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                    text-align: center;
                }
                .button-container {
                    text-align: center;
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
                            <div class="badge">Account Deleted</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Your Account Has Been Deleted</h1>
                            <p class="subtitle">Hello {{ $user->first_name }}, this email confirms that your {{ config('app.name') }} account ({{ $user->email }}) was successfully deleted on {{ Carbon::now()->setTimezone('Africa/Lagos')->format('F j, Y') }}.</p>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/recurring-appointment.png" alt="Recovery Icon">
                                    Account Recovery Option
                                </h3>
                                <p>If this was a mistake, you can recover your account within <strong>30 days</strong> by contacting our support team. After this period, all your data will be permanently erased.</p>
                            </div>

                            <div class="warning-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/ef4444/error.png" alt="Warning Icon">
                                    Didn't Request This?
                                </h3>
                                <p>If you did not request this account deletion, please contact our support team immediately to help secure your account.</p>
                                <div class="button-container">
                                    <a href="mailto:{{ config('settings.site.site_email') }}" class="button">Contact Support Immediately</a>
                                </div>
                            </div>

                            <div class="support-text">
                                <p>We're sorry to see you go. If you have any feedback or need help with recovery, please contact us at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
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
