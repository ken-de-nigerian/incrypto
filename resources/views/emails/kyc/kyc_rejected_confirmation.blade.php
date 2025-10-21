<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>KYC Submission Rejected - {{ config('app.name') }}</title>
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
                margin: 0 auto 12px;
            }
            .badge {
                display: inline-block;
                background-color: #fee2e2;
                color: #991b1b;
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
            .details-card {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                margin: 24px 0;
                text-align: left;
            }
            .details-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 16px;
            }
            .details-table td {
                padding: 0;
                vertical-align: middle;
                font-size: 15px;
            }
            .details-table .label-cell {
                width: 40%;
            }
            .details-table .icon {
                width: 20px;
                height: 20px;
                margin-right: 12px;
                vertical-align: middle;
            }
            .details-table .label {
                font-weight: 600;
                color: #334155;
                vertical-align: middle;
            }
            .details-table .value {
                color: #1f2a44;
                font-weight: 500;
                text-align: right;
                word-break: break-all;
            }
            .warning-box {
                background: #fef2f2;
                border-left: 4px solid #dc2626;
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }
            .warning-box h3 {
                margin: 0 0 12px;
                color: #dc2626;
                font-size: 17px;
                display: flex;
                align-items: center;
            }
            .warning-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 12px;
            }
            .warning-box p {
                margin: 0 0 12px;
                color: #7f1d1d;
                font-size: 15px;
                line-height: 1.6;
            }
            .reason-section {
                background-color: #fff5f5;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
                text-align: left;
            }
            .reason-label {
                font-size: 13px;
                font-weight: 700;
                color: #7f1d1d;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 8px;
            }
            .reason-text {
                color: #5a1f1f;
                font-size: 15px;
                line-height: 1.6;
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
            .button-container {
                margin: 32px 0;
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

                .details-card {
                    padding: 0;
                    background-color: transparent;
                    border: 0;
                }
                .details-table, .details-table tbody {
                    display: block;
                    width: 100%;
                }
                .details-table tr {
                    display: block;
                    width: 100%;
                    background-color: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 10px;
                    padding: 16px;
                    margin-bottom: 12px;
                    box-sizing: border-box;
                }
                .details-table td {
                    display: block;
                    width: 100% !important;
                    text-align: left !important;
                    padding: 0 !important;
                }
                .details-table .label-cell {
                    font-size: 11px;
                    font-weight: 700;
                    color: #64748b;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .details-table .icon {
                    display: none;
                }
                .details-table .label {
                    font-weight: 700;
                }
                .details-table .value {
                    font-size: 16px;
                    font-weight: 600;
                    padding-top: 4px;
                }
                .warning-box {
                    padding: 16px;
                }
                .info-box {
                    padding: 16px;
                }
                .button {
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
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
                            <div class="badge">Submission Rejected</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Submission Requires Revision</h1>
                            <p class="subtitle">Hello {{ $user->first_name }}, your KYC submission has been reviewed and requires some corrections before we can proceed.</p>

                            <div class="warning-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/dc2626/error.png" alt="Error Icon">
                                    Action Required
                                </h3>
                                <p>We've identified some issues with your submission that need to be addressed. Please review the details below and resubmit your documents.</p>
                            </div>

                            <div class="reason-section">
                                <div class="reason-label">Reason for Rejection</div>
                                <div class="reason-text">{{ $rejection_reason }}</div>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/info.png" alt="Info Icon">
                                    What to Do Next
                                </h3>
                                <ul>
                                    <li>Review the rejection reason provided above carefully.</li>
                                    <li>Gather the necessary corrected or additional documents.</li>
                                    <li>Resubmit your KYC documents through your dashboard.</li>
                                    <li>Our team will review your new submission within <strong>24-48 business hours</strong>.</li>
                                </ul>
                            </div>

                            <div class="button-container">
                                <a href="{{ route('user.dashboard') }}" class="button">Go to Dashboard & Resubmit</a>
                            </div>

                            <div class="support-text">
                                <p>Need help? Contact our support team at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
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
