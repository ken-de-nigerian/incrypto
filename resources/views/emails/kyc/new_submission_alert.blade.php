<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>New KYC Submission for Review - {{ config('app.name') }}</title>
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
                text-align: left;
                margin: 24px 0;
                background: #f8fafc;
                padding: 20px;
                border-radius: 8px;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 12px;
                font-size: 15px;
            }
            .detail-label {
                font-weight: 600;
                color: #1f2a44;
                flex: 1;
            }
            .detail-value {
                color: #1f2a44;
                flex: 2;
                word-break: break-word;
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
                    margin: 16px 0;
                    padding: 16px;
                }
                .detail-row {
                    flex-direction: column;
                    margin-bottom: 10px;
                    font-size: 14px;
                }
                .detail-label, .detail-value {
                    flex: none;
                }
                .detail-value {
                    margin-top: 4px;
                }
                .button {
                    padding: 10px 24px;
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
                                <div class="success-badge">Action Required</div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">New KYC Submission</h1>
                            <p class="subtitle">A new KYC submission has been received and requires your review.</p>

                            <div class="activity-details">
                                <h3 style="margin: 0 0 16px; font-size: 18px; color: #1f2a44;">👤 User Details</h3>
                                <div class="detail-row">
                                    <span class="detail-label">User Name:</span>
                                    <span class="detail-value">{{ $submission->user->first_name }} {{ $submission->user->last_name }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">User Email:</span>
                                    <span class="detail-value">{{ $submission->user->email }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Submission ID:</span>
                                    <span class="detail-value">{{ $submission->id }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Date Submitted:</span>
                                    <span class="detail-value">{{ $submission->created_at->format('F j, Y, g:i A') }}</span>
                                </div>
                            </div>

                            <p style="margin: 24px 0; color: #1f2a44;">Please review the submission in the admin dashboard to approve or reject it.</p>

                            <a href="{{ route('admin.kyc.show', $submission->id) }}" class="button">Review Submission</a>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>Building the future of digital finance</p>
                            <div class="social-links">
                                <a href="{{ config('settings.social.site_fb') }}" class="social-link" title="Facebook">
                                    <img src="https://img.icons8.com/color/28/000000/facebook-new.png" alt="Facebook Icon" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_instagram') }}" class="social-link" title="Instagram">
                                    <img src="https://img.icons8.com/color/28/000000/instagram.png" alt="Instagram Icon" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_linkedin') }}" class="social-link" title="LinkedIn">
                                    <img src="https://img.icons8.com/color/28/000000/linkedin.png" alt="LinkedIn Icon" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_youtube') }}" class="social-link" title="YouTube">
                                    <img src="https://img.icons8.com/color/28/000000/youtube-play.png" alt="YouTube Icon" class="social-img">
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
