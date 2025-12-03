<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>New Loan Request - {{ config('app.name') }}</title>
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
                background-color: #dc2626;
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
            .user-info-box {
                background: #f0f9ff;
                border: 2px solid #0ea5e9;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
            }
            .user-info-box h3 {
                margin: 0 0 16px;
                color: #0369a1;
                font-size: 18px;
                text-align: center;
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
                background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
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
                background-color: #dc2626;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background-color: #b91c1c;
            }
            .info-box {
                background: #fef2f2;
                border-left: 4px solid #dc2626;
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 12px;
                color: #dc2626;
                font-size: 17px;
                display: flex;
                align-items: center;
            }
            .info-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 8px;
            }
            .info-box p {
                margin: 0 0 12px;
                color: #991b1b;
                font-size: 14px;
                line-height: 1.6;
            }
            .reason-box {
                background: #fffbeb;
                border: 1px solid #fcd34d;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
            }
            .reason-box h3 {
                margin: 0 0 12px;
                color: #92400e;
                font-size: 16px;
            }
            .reason-box p {
                margin: 0;
                color: #78350f;
                font-size: 14px;
                line-height: 1.6;
            }
            .urgent-banner {
                background: #fee2e2;
                border: 2px solid #dc2626;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
                text-align: center;
            }
            .urgent-banner p {
                margin: 0;
                color: #991b1b;
                font-size: 15px;
                font-weight: 700;
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
                .button {
                    padding: 12px 24px;
                    font-size: 15px;
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
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
                            <div class="badge">New Loan Request</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">New Loan Application Received</h1>
                            <p class="subtitle">A new loan request has been submitted by {{ $loan->user->first_name }} {{ $loan->user->last_name }} and requires your review.</p>

                            <div class="urgent-banner">
                                <p>⚡ Action Required: Please review and process this loan request</p>
                            </div>

                            <div class="user-info-box">
                                <h3>👤 Applicant Information</h3>
                                <div class="detail-row">
                                    <span class="detail-label">Full Name:</span>
                                    <span class="detail-value">{{ $loan->user->first_name }} {{ $loan->user->last_name }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">{{ $loan->user->email }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value">{{ $loan->user->phone ?? 'N/A' }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">User ID:</span>
                                    <span class="detail-value">#{{ $loan->user->id }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Submission Date:</span>
                                    <span class="detail-value">{{ $loan->created_at->format('M d, Y h:i A') }}</span>
                                </div>
                            </div>

                            <div class="highlight-box">
                                <h3>Requested Loan Amount</h3>
                                <div class="amount">${{ number_format($loan->loan_amount, 2) }}</div>
                                <p>EMI: ${{ number_format($loan->monthly_emi, 2) }}/month × {{ $loan->tenure_months }} months</p>
                            </div>

                            <div class="loan-details-box">
                                <div class="detail-row">
                                    <span class="detail-label">Loan Title:</span>
                                    <span class="detail-value">{{ $loan->title }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Loan Amount:</span>
                                    <span class="detail-value">${{ number_format($loan->loan_amount, 2) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Interest Rate:</span>
                                    <span class="detail-value" style="color: #dc2626;">{{ number_format($loan->interest_rate, 2) }}% p.a.</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Tenure:</span>
                                    <span class="detail-value">{{ $loan->tenure_months }} {{ $loan->tenure_months > 1 ? 'months' : 'month' }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Monthly EMI:</span>
                                    <span class="detail-value" style="color: #10b981;">${{ number_format($loan->monthly_emi, 2) }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Total Interest:</span>
                                    <span class="detail-value">${{ number_format($loan->total_interest, 2) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Total Repayment:</span>
                                    <span class="detail-value" style="color: #1f2a44; font-size: 17px;">${{ number_format($loan->total_payment, 2) }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Loan ID:</span>
                                    <span class="detail-value">#{{ $loan->id }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" style="color: #f59e0b;">
                                                {{ ucfirst($loan->status) }}
                                            </span>
                                </div>
                            </div>

                            @if($loan->loan_reason)
                                <div class="reason-box">
                                    <h3>📋 Loan Purpose</h3>
                                    <p>{{ $loan->loan_reason }}</p>
                                </div>
                            @endif

                            @if($loan->loan_collateral)
                                <div class="reason-box">
                                    <h3>🏦 Collateral Information</h3>
                                    <p>{{ $loan->loan_collateral }}</p>
                                </div>
                            @endif

                            <div class="cta-section">
                                <a href="{{ route('admin.loans.index') }}" class="button">Review Loan Application</a>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/dc2626/info.png" alt="Info Icon">
                                    Next Steps
                                </h3>
                                <p>• Review the applicant's information and loan details carefully.</p>
                                <p>• Verify the applicant's creditworthiness and financial history.</p>
                                <p>• Check if additional documentation is required.</p>
                                <p>• Approve, reject, or request more information from the applicant.</p>
                                <p>• The applicant is awaiting your decision and will be notified once you take action.</p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>This is an automated notification for new loan requests.</p>
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
