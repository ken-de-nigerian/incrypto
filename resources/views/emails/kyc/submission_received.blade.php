<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>KYC Submission Received - {{ config('app.name') }}</title>
        <style>
            /* CSS from your original template */
            body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, Helvetica, sans-serif; background: #f1f5f9; color: #1a202c; line-height: 1.6; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; width: 100% !important; min-width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table { border-collapse: collapse; width: 100%; max-width: 600px; }
            img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; max-width: 100%; height: auto; }
            a { text-decoration: none; color: inherit; }
            .email-wrapper { width: 100%; background: #f1f5f9; padding: 20px 0; }
            .container { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
            .header { background: #000000; padding: 30px; text-align: center; }
            .logo-container { text-align: center; }
            .logo-img { max-width: 150px; height: auto; display: block; margin: 0 auto 10px; }
            .success-badge { display: inline-block; background: rgba(255, 255, 255, 0.95); color: #000000; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
            .content { padding: 30px 20px; text-align: center; }
            .greeting { font-size: 24px; font-weight: bold; margin: 0 0 10px; color: #000000; }
            .subtitle { font-size: 16px; color: #6b7280; margin: 0 0 20px; }
            .activity-details { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: left; }
            .activity-details h3 { margin: 0 0 15px; color: #1f2937; font-size: 16px; font-weight: 600; }
            .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: 600; color: #374151; min-width: 120px; font-size: 14px; }
            .detail-value { color: #6b7280; font-size: 14px; flex: 1; word-break: break-word; }
            .info-box { background: #dbeafe; border: 1px solid #93c5fd; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: left; }
            .info-box h3 { margin: 0 0 10px; color: #1e40af; font-size: 16px; }
            .info-box ul { margin: 0; padding-left: 20px; color: #1d4ed8; font-size: 14px; }
            .info-box li { margin: 5px 0; }
            .button { display: inline-block; padding: 12px 24px; background: #16a34a; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; margin-top: 10px; }
            .support-text { background: #f3f4f6; border-radius: 8px; padding: 15px; margin: 20px 0; }
            .support-text p { margin: 0; color: #374151; font-size: 14px; }
            .support-email { color: #1f2937 !important; font-weight: bold; text-decoration: none; }
            .footer { background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; }
            .footer p { margin: 4px 0; font-size: 12px; color: #6b7280; }
            .social-links { margin: 15px 0 0; }
            .social-link { display: inline-block; margin: 0 6px; text-decoration: none; }
            .social-img { width: 24px; height: 24px; vertical-align: middle; }
            @media only screen and (max-width: 600px) { /* Responsive styles from original */ }
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
                                <div class="success-badge">✓ Submission Received</div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">KYC Submission Received</h1>
                            <p class="subtitle">Hello, thank you for submitting your KYC documents. Your information is now under review.</p>

                            <div class="activity-details">
                                <h3>📋 Submission Details</h3>
                                <div class="detail-row">
                                    <span class="detail-label">Submission Time:</span>
                                    <span class="detail-value">{{ now()->format('F j, Y \a\t g:i A') }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Current Status:</span>
                                    <span class="detail-value">Pending Review</span>
                                </div>
                            </div>

                            <div class="info-box">
                                <h3>What Happens Next?</h3>
                                <ul>
                                    <li>Our compliance team will review your submission within the next 24-48 business hours.</li>
                                    <li>You will receive an email notification as soon as the review is complete.</li>
                                    <li>No further action is required from you at this time.</li>
                                </ul>
                            </div>

                            <a href="{{ route('user.dashboard') }}" class="button">Go to My Dashboard</a>

                            <div class="support-text">
                                <p>If you have any questions, please contact our support team at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
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
