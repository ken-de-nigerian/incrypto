<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Loan Approved - {{ config('app.name') }}</title>
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
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                padding: 32px 24px;
                text-align: center;
            }
            .logo-img {
                max-width: 140px;
                margin: 0 auto 16px;
            }
            .badge {
                display: inline-block;
                background-color: #d1fae5;
                color: #065f46;
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
            .celebration-icon {
                text-align: center;
                font-size: 64px;
                margin: 0 0 16px;
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
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
                background-color: #10b981;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background-color: #059669;
            }
            .info-box {
                background: #ecfdf5;
                border-left: 4px solid #10b981;
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 12px;
                color: #059669;
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
                color: #065f46;
                font-size: 14px;
            }
            .info-box li {
                margin: 6px 0;
                line-height: 1.5;
            }
            .status-approved {
                background: #d1fae5;
                border: 1px solid #10b981;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
                text-align: center;
            }
            .status-approved p {
                margin: 0;
                color: #065f46;
                font-size: 14px;
                font-weight: 600;
            }
            .admin-notes-box {
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
            }
            .admin-notes-box h3 {
                margin: 0 0 8px;
                color: #1f2a44;
                font-size: 15px;
                font-weight: 600;
            }
            .admin-notes-box p {
                margin: 0;
                color: #475569;
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
                color: #10b981 !important;
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
                .celebration-icon {
                    font-size: 48px;
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
                            <div class="badge">✓ Loan Approved</div>
                        </div>

                        <div class="content">
                            <div class="celebration-icon">🎉</div>
                            <h1 class="greeting">Congratulations! Your Loan is Approved</h1>
                            <p class="subtitle">Great news, {{ $user->first_name }}! Your loan request for "{{ $loan->title }}" has been approved and the funds will be disbursed shortly.</p>

                            <div class="status-approved">
                                <p>✅ Your loan has been approved! The amount will be credited to your account within 24-48 hours.</p>
                            </div>

                            <div class="highlight-box">
                                <h3>Approved Loan Amount</h3>
                                <div class="amount">${{ number_format($loan->loan_amount, 2) }}</div>
                                <p>Monthly EMI: ${{ number_format($loan->monthly_emi, 2) }}</p>
                            </div>

                            <div class="loan-details-box">
                                <div class="detail-row">
                                    <span class="detail-label">Loan Title:</span>
                                    <span class="detail-value">{{ $loan->title }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Approved Amount:</span>
                                    <span class="detail-value">${{ number_format($loan->loan_amount, 2) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Interest Rate:</span>
                                    <span class="detail-value" style="color: #10b981;">{{ number_format($loan->interest_rate, 2) }}% per annum</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Loan Tenure:</span>
                                    <span class="detail-value">{{ $loan->tenure_months }} {{ $loan->tenure_months > 1 ? 'months' : 'month' }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Monthly EMI:</span>
                                    <span class="detail-value" style="color: #10b981;">${{ number_format($loan->monthly_emi, 2) }}</span>
                                </div>

                                @if($loan->due_date)
                                    <div class="detail-row">
                                        <span class="detail-label">First EMI Due Date:</span>
                                        <span class="detail-value" style="color: #f59e0b;">{{ date('M d, Y', strtotime($loan->due_date)) }}</span>
                                    </div>
                                @endif

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
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" style="color: #10b981;">
                                                Approved
                                            </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Approved On:</span>
                                    <span class="detail-value">{{ date('M d, Y', strtotime($loan->disbursed_at ?? now())) }}</span>
                                </div>
                            </div>

                            @if($loan->remarks)
                                <div class="admin-notes-box">
                                    <h3>💬 Note from Our Team</h3>
                                    <p>{{ $loan->remarks }}</p>
                                </div>
                            @endif

                            <div class="cta-section">
                                <a href="{{ route('user.trade.loans') }}" class="button">View Loan Dashboard</a>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/10b981/info.png" alt="Info Icon">
                                    What Happens Next?
                                </h3>

                                <ul>
                                    <li>Your loan amount will be disbursed to your registered account within 24-48 hours.</li>
                                    <li>You will receive a confirmation once the funds are transferred.</li>
                                    <li>Your first EMI payment of ${{ number_format($loan->monthly_emi, 2) }} will be due {{ $loan->due_date ? 'on ' . date('M d, Y', strtotime($loan->due_date)) : 'as per the schedule' }}.</li>
                                    <li>Set up auto-debit to ensure timely EMI payments and avoid late fees.</li>
                                    <li>You can view your complete repayment schedule and track payments in your dashboard.</li>
                                    <li>Contact us anytime if you need assistance or have questions about your loan.</li>
                                </ul>
                            </div>

                            @if($loan->loan_reason)
                                <div class="loan-details-box">
                                    <h3 style="margin: 0 0 12px; color: #1f2a44; font-size: 16px;">Loan Purpose</h3>
                                    <p style="margin: 0; color: #64748b; font-size: 14px; line-height: 1.6;">{{ $loan->loan_reason }}</p>
                                </div>
                            @endif

                            <div class="support-text">
                                <p><strong>Questions about your approved loan?</strong> Our support team is here to help.</p>
                                <p><a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a></p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>You received this email because your loan request was approved on our platform.</p>
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
