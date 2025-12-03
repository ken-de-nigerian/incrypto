<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Loan Application Update - {{ config('app.name') }}</title>
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
                background: linear-gradient(135deg, #64748b 0%, #475569 100%);
                padding: 32px 24px;
                text-align: center;
            }
            .logo-img {
                max-width: 140px;
                margin: 0 auto 16px;
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
            }
            .greeting {
                font-size: 26px;
                font-weight: 700;
                margin: 0 0 12px;
                color: #1f2a44;
                text-align: center;
            }
            .subtitle {
                font-size: 16px;
                color: #64748b;
                text-align: center;
                max-width: 90%;
                margin: 0 auto 32px;
            }
            .loan-details-box {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
            }
            .detail-row {
                display: table;
                width: 100%;
                padding: 8px 0;
            }
            .detail-label, .detail-value {
                display: table-cell;
                font-size: 15px;
                color: #475569;
                padding-bottom: 5px;
            }
            .detail-label {
                text-align: left;
                width: 45%;
                font-weight: 500;
            }
            .detail-value {
                text-align: right;
                width: 55%;
                font-weight: 700;
                color: #1f2a44;
            }
            .separator {
                border-top: 1px solid #e2e8f0;
                margin: 10px 0;
            }
            .highlight-box {
                background: linear-gradient(135deg, #64748b 0%, #475569 100%);
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: center;
                color: #ffffff;
            }
            .highlight-box h3 {
                margin: 0 0 8px;
                font-size: 18px;
                font-weight: 700;
            }
            .highlight-box .amount {
                font-size: 32px;
                font-weight: 800;
                margin: 8px 0;
            }
            .highlight-box p {
                margin: 4px 0;
                font-size: 14px;
                opacity: 0.9;
            }
            .cta-section {
                margin: 32px 0;
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
                background-color: #2d3748;
            }
            .button-secondary {
                display: inline-block;
                padding: 14px 32px;
                background-color: transparent;
                border: 2px solid #1f2a44;
                color: #1f2a44 !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: all 0.2s ease;
                margin-left: 12px;
            }
            .button-secondary:hover {
                background-color: #1f2a44;
                color: #ffffff !important;
            }
            .info-box {
                background: #fffbeb;
                border-left: 4px solid #f59e0b;
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 12px;
                color: #d97706;
                font-size: 17px;
                display: flex;
                align-items: center;
            }
            .info-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 8px;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #92400e;
                font-size: 14px;
            }
            .info-box li {
                margin: 6px 0;
                line-height: 1.5;
            }
            .status-rejected {
                background: #fee2e2;
                border: 1px solid #ef4444;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
                text-align: center;
            }
            .status-rejected p {
                margin: 0;
                color: #991b1b;
                font-size: 14px;
                font-weight: 600;
            }
            .rejection-reason-box {
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
            }
            .rejection-reason-box h3 {
                margin: 0 0 12px;
                color: #dc2626;
                font-size: 16px;
                font-weight: 600;
            }
            .rejection-reason-box p {
                margin: 0;
                color: #7f1d1d;
                font-size: 14px;
                line-height: 1.6;
            }
            .support-text {
                text-align: center;
                padding-top: 24px;
                border-top: 1px solid #e2e8f0;
            }
            .support-text p {
                margin: 0 0 8px;
                color: #64748b;
                font-size: 14px;
            }
            .support-email {
                color: #1f2a44 !important;
                font-weight: 600;
                text-decoration: underline;
            }
            .support-email:hover {
                color: #ef4444 !important;
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
            .encouragement-box {
                background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: center;
            }
            .encouragement-box h3 {
                margin: 0 0 8px;
                color: #3730a3;
                font-size: 18px;
                font-weight: 700;
            }
            .encouragement-box p {
                margin: 0;
                color: #4338ca;
                font-size: 14px;
            }

            @media only screen and (max-width: 640px) {
                .email-wrapper {
                    padding: 0;
                }
                .container {
                    border-radius: 0;
                    border-left: 0;
                    border-right: 0;
                }
                .content {
                    padding: 24px 16px;
                }
                .greeting {
                    font-size: 22px;
                }
                .subtitle {
                    font-size: 15px;
                }
                .button, .button-secondary {
                    padding: 12px 24px;
                    font-size: 15px;
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                    margin: 8px 0;
                }
                .button-secondary {
                    margin-left: 0;
                }
                .highlight-box .amount {
                    font-size: 28px;
                }
                .footer p {
                    font-size: 12px;
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
                            <div class="badge">Application Update</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Loan Application Update</h1>
                            <p class="subtitle">Hi {{ $user->first_name }}, thank you for your loan application. After careful review, we regret to inform you that we are unable to approve your loan request at this time.</p>

                            <div class="status-rejected">
                                <p>❌ Your loan application for "{{ $loan->title }}" was not approved</p>
                            </div>

                            @if($loan->remarks)
                                <div class="rejection-reason-box">
                                    <h3>📋 Reason for Decline</h3>
                                    <p>{{ $loan->remarks }}</p>
                                </div>
                            @endif

                            <div class="highlight-box">
                                <h3>Application Details</h3>
                                <div class="amount">${{ number_format($loan->loan_amount, 2) }}</div>
                                <p>Requested Amount</p>
                            </div>

                            <div class="loan-details-box">
                                <div class="detail-row">
                                    <span class="detail-label">Loan Title:</span>
                                    <span class="detail-value">{{ $loan->title }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Requested Amount:</span>
                                    <span class="detail-value">${{ number_format($loan->loan_amount, 2) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Interest Rate:</span>
                                    <span class="detail-value">{{ number_format($loan->interest_rate, 2) }}% per annum</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Tenure:</span>
                                    <span class="detail-value">{{ $loan->tenure_months }} {{ $loan->tenure_months > 1 ? 'months' : 'month' }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Application Date:</span>
                                    <span class="detail-value">{{ date('M d, Y', strtotime($loan->created_at)) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" style="color: #ef4444;">
                                        Not Approved
                                    </span>
                                </div>
                            </div>

                            <div class="encouragement-box">
                                <h3>💙 Don't Give Up!</h3>
                                <p>While we couldn't approve this application, you're welcome to apply again in the future. We encourage you to work on the areas mentioned and reapply when ready.</p>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/f59e0b/info.png" alt="Info Icon">
                                    What You Can Do Next
                                </h3>

                                <ul>
                                    <li>Review the reason for decline and work on addressing any concerns.</li>
                                    <li>Consider improving your credit score or financial documentation.</li>
                                    <li>You may apply for a different loan amount or tenure that better fits your current situation.</li>
                                    <li>Contact our support team if you have questions or need clarification.</li>
                                    <li>You can reapply after 30 days once you've addressed the concerns.</li>
                                    <li>Explore other financial products and services we offer that might suit your needs.</li>
                                </ul>
                            </div>

                            <div class="cta-section">
                                <a href="{{ route('user.trade.loans') }}" class="button">View Dashboard</a>
                                <a href="mailto:{{ config('settings.site.site_email') }}" class="button-secondary">Contact Support</a>
                            </div>

                            @if($loan->loan_reason)
                                <div class="loan-details-box">
                                    <h3 style="margin: 0 0 12px; color: #1f2a44; font-size: 16px;">Your Stated Loan Purpose</h3>
                                    <p style="margin: 0; color: #64748b; font-size: 14px; line-height: 1.6;">{{ $loan->loan_reason }}</p>
                                </div>
                            @endif

                            <div class="support-text">
                                <p><strong>Questions about your application?</strong> We're here to help you understand the decision.</p>
                                <p><a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a></p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>You received this email regarding your loan application on our platform.</p>
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
