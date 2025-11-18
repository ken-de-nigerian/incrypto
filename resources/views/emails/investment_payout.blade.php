@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Investment Payout - {{ config('app.name') }}</title>
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
                background-color: #dcfce7;
                color: #166534;
                padding: 8px 20px;
                border-radius: 9999px;
                font-size: 14px;
                font-weight: 700;
            }

            .badge-final {
                background-color: #fef3c7;
                color: #92400e;
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

            .investment-details-box {
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

            .highlight-box-final {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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

            .cycle-progress {
                background: #f8fafc;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
                text-align: center;
            }

            .cycle-progress h4 {
                margin: 0 0 12px;
                color: #475569;
                font-size: 14px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .progress-bar {
                background: #e2e8f0;
                height: 12px;
                border-radius: 9999px;
                overflow: hidden;
                margin: 12px 0;
            }

            .progress-fill {
                background: linear-gradient(90deg, #10b981 0%, #059669 100%);
                height: 100%;
                transition: width 0.3s ease;
            }

            .cycle-text {
                font-size: 16px;
                font-weight: 700;
                color: #1f2a44;
                margin: 8px 0 0;
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

            .info-box {
                background: #f0f9ff;
                border-left: 4px solid #0369a1;
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }

            .success-box {
                background: #f0fdf4;
                border-left: 4px solid #10b981;
            }

            .warning-box {
                background: #fffbeb;
                border-left: 4px solid #f59e0b;
            }

            .info-box h3 {
                margin: 0 0 12px;
                color: #0369a1;
                font-size: 17px;
                display: flex;
                align-items: center;
            }

            .success-box h3 {
                color: #166534;
            }

            .warning-box h3 {
                color: #92400e;
            }

            .info-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 8px;
            }

            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #0c4a6e;
                font-size: 14px;
            }

            .success-box ul {
                color: #166534;
            }

            .warning-box ul {
                color: #92400e;
            }

            .info-box li {
                margin: 6px 0;
                line-height: 1.5;
            }

            .summary-box {
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                border-radius: 8px;
                padding: 24px;
                margin: 24px 0;
                color: #ffffff;
            }

            .summary-box h3 {
                margin: 0 0 16px;
                font-size: 18px;
                font-weight: 700;
                text-align: center;
            }

            .summary-row {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .summary-row:last-child {
                border-bottom: none;
                padding-top: 16px;
                font-size: 18px;
                font-weight: 700;
            }

            .summary-label {
                opacity: 0.9;
            }

            .summary-value {
                font-weight: 700;
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

                .summary-row {
                    font-size: 14px;
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
                            <div class="badge {{ $payoutData['is_final_cycle'] ? 'badge-final' : '' }}">
                                {{ $payoutData['is_final_cycle'] ? '🎉 Investment Completed' : '💰 Payout Received' }}
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">
                                @if($payoutData['is_final_cycle'])
                                    Congratulations! Investment Completed
                                @else
                                    Cycle {{ $payoutData['cycle'] }} Payout Received!
                                @endif
                            </h1>

                            <p class="subtitle">
                                @if($payoutData['is_final_cycle'])
                                    Hi {{ $user->first_name }}, your investment
                                    in {{ $investment->plan->name ?? 'the selected plan' }} has completed
                                    all {{ $payoutData['total_cycles'] }} cycles. Your final payout has been credited to your
                                    account.
                                @else
                                    Hi {{ $user->first_name }}, great news! Cycle {{ $payoutData['cycle'] }} of your investment
                                    in {{ $investment->plan->name ?? 'the selected plan' }} has matured and your payout has been
                                    processed.
                                @endif
                            </p>

                            <div class="highlight-box {{ $payoutData['is_final_cycle'] ? 'highlight-box-final' : '' }}">
                                <h3>{{ $payoutData['is_final_cycle'] ? 'Final Payout' : 'Cycle Payout' }}</h3>
                                <div class="amount">${{ number_format($payoutData['payout_amount'], 2) }}</div>
                                <p>Interest: ${{ number_format($payoutData['interest'], 2) }}</p>
                                @if($payoutData['capital_returned'])
                                    <p>+ Principal: ${{ number_format($investment->amount, 2) }}</p>
                                @endif
                            </div>

                            @if(!$payoutData['is_final_cycle'])
                                <div class="cycle-progress">
                                    <h4>Investment Progress</h4>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: {{ ($payoutData['cycle'] / $payoutData['total_cycles']) * 100 }}%;"></div>
                                    </div>
                                    <p class="cycle-text">
                                        Cycle {{ $payoutData['cycle'] }} of {{ $payoutData['total_cycles'] }} completed
                                    </p>
                                </div>
                            @endif

                            <div class="investment-details-box">
                                <div class="detail-row">
                                    <span class="detail-label">Investment Plan:</span>
                                    <span class="detail-value">{{ $investment->plan->name ?? 'N/A' }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Principal Amount:</span>
                                    <span class="detail-value">${{ number_format($investment->amount, 2) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Interest Per Cycle:</span>
                                    <span class="detail-value" style="color: #10b981;">${{ number_format($payoutData['interest'], 2) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Cycle {{ $payoutData['cycle'] }} Payout:</span>
                                    <span class="detail-value" style="color: #10b981;">${{ number_format($payoutData['payout_amount'], 2) }}</span>
                                </div>

                                @if($payoutData['is_final_cycle'])
                                    <div class="separator"></div>
                                    <div class="detail-row">
                                        <span class="detail-label">Total Cycles Completed:</span>
                                        <span class="detail-value">{{ $payoutData['total_cycles'] }}</span>
                                    </div>
                                @else
                                    <div class="detail-row">
                                        <span class="detail-label">Remaining Cycles:</span>
                                        <span class="detail-value">{{ $payoutData['total_cycles'] - $payoutData['cycle'] }}</span>
                                    </div>
                                @endif
                            </div>

                            @if($payoutData['is_final_cycle'])
                                <div class="summary-box">
                                    <h3>Investment Summary</h3>
                                    <div class="summary-row">
                                        <span class="summary-label">Total Interest Earned:</span>
                                        <span class="summary-value">${{ number_format($payoutData['interest'] * $payoutData['total_cycles'], 2) }}</span>
                                    </div>
                                    @if($payoutData['capital_returned'])
                                        <div class="summary-row">
                                            <span class="summary-label">Principal Returned:</span>
                                            <span class="summary-value">${{ number_format($investment->amount, 2) }}</span>
                                        </div>
                                        <div class="summary-row">
                                            <span class="summary-label">Total Received:</span>
                                            <span class="summary-value">${{ number_format(($payoutData['interest'] * $payoutData['total_cycles']) + $investment->amount, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="cta-section">
                                <a href="{{ route('user.trade.investment') }}" class="button">View Investment Dashboard</a>
                            </div>

                            @if($payoutData['is_final_cycle'])
                                <div class="info-box success-box">
                                    <h3>
                                        <img src="https://img.icons8.com/fluency-systems-filled/48/10b981/checkmark.png" alt="Success Icon">
                                        Investment Completed Successfully
                                    </h3>
                                    <ul>
                                        <li>Your investment has completed all {{ $payoutData['total_cycles'] }} cycles.</li>
                                        <li>Total interest earned:
                                            ${{ number_format($payoutData['interest'] * $payoutData['total_cycles'], 2) }}</li>
                                        @if($payoutData['capital_returned'])
                                            <li>Your principal of ${{ number_format($investment->amount, 2) }} has been
                                                returned.
                                            </li>
                                        @endif
                                        <li>The final payout of ${{ number_format($payoutData['payout_amount'], 2) }} has been
                                            credited to your live trading balance.
                                        </li>
                                        <li>Thank you for investing with us! We hope to serve you again soon.</li>
                                    </ul>
                                </div>
                            @else
                                <div class="info-box">
                                    <h3>
                                        <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/info.png" alt="Info Icon">
                                        What's Next?
                                    </h3>
                                    <ul>
                                        <li>Your payout of ${{ number_format($payoutData['payout_amount'], 2) }} has been
                                            credited to your live trading balance.
                                        </li>
                                        <li>Your investment will continue
                                            for {{ $payoutData['total_cycles'] - $payoutData['cycle'] }}
                                            more {{ ($payoutData['total_cycles'] - $payoutData['cycle']) > 1 ? 'cycles' : 'cycle' }}
                                            .
                                        </li>
                                        <li>The next payout will be processed
                                            on {{ Carbon::parse($investment->next_time)->format('M d, Y \a\t h:i A') }}.
                                        </li>
                                        <li>You will receive ${{ number_format($payoutData['interest'], 2) }} per cycle.</li>
                                        @if($payoutData['capital_returned'])
                                            <li>Your principal will be returned at the end of the final cycle.</li>
                                        @endif
                                    </ul>
                                </div>
                            @endif

                            <div class="support-text">
                                <p><strong>Questions about your {{ $payoutData['is_final_cycle'] ? 'completed investment' : 'payout' }}?</strong> Our support team is here to help.</p>
                                <p><a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a></p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>You received this email because you have an active investment on our platform.</p>
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
