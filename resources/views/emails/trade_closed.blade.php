<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Trade Closed - {{ config('app.name') }}</title>
        <style>
            /* --- STYLES REMAINING UNCHANGED FOR BREVITY --- */
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
            /* --- MODIFIED BADGE COLORS AND TEXT --- */
            .badge {
                display: inline-block;
                /* Success/Profit */
                background-color: {{ $trade->pnl >= 0 ? '#dcfce7' : '#fee2e2' }};
                color: {{ $trade->pnl >= 0 ? '#166534' : '#991b1b' }};
                padding: 8px 20px;
                border-radius: 9999px;
                font-size: 14px;
                font-weight: 700;
            }
            /* --- END MODIFIED BADGE --- */
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
            /* --- Trade Details Styling --- */
            .trade-details-box {
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
                width: 40%;
                font-weight: 500;
            }
            .detail-value {
                text-align: right;
                width: 60%;
                font-weight: 700;
                color: #1f2a44;
            }
            /* --- Added styling for PnL row --- */
            .pnl-value {
                font-size: 18px !important;
                font-weight: 900 !important;
                /* Green for Profit, Red for Loss */
                color: {{ $trade->pnl >= 0 ? '#16a34a' : '#ef4444' }} !important;
            }
            .separator {
                border-top: 1px solid #e2e8f0;
                margin: 10px 0;
            }
            /* --- End Trade Details Styling --- */
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
                margin-right: 8px;
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
                            <div class="badge">Trade {{ $trade->pnl >= 0 ? 'Profitable' : 'Closed at Loss' }}</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Trade Closed Successfully!</h1>
                            <p class="subtitle">
                                Hi {{ $user->first_name }}, your {{ ucfirst($trade->trading_mode) }} trade on {{ $trade->pair }} has closed with a final result of
                                <span class="pnl-value" style="font-size: 16px;">{{ $trade->pnl >= 0 ? '+' : '-' }}${{ number_format(abs($trade->pnl), 2) }}</span>.
                            </p>

                            <div class="trade-details-box">
                                <div class="detail-row">
                                    <span class="detail-label">Asset Pair:</span>
                                    <span class="detail-value">{{ $trade->pair }}</span>
                                </div>

                                <div class="separator"></div>
                                <div class="detail-row">
                                    <span class="detail-label">Trade Type:</span>
                                    <span class="detail-value" style="color: {{ $trade->type === 'buy' ? '#16a34a' : '#ef4444' }};">{{ ucfirst($trade->type) }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Amount Invested:</span>
                                    <span class="detail-value">${{ number_format($trade->amount, 2) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Entry Price:</span>
                                    <span class="detail-value">{{ number_format($trade->entry_price, 5) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Exit Price:</span>
                                    <span class="detail-value">{{ number_format($trade->exit_price, 5) }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Profit/Loss (PnL):</span>
                                    <span class="detail-value pnl-value">
                                                {{ $trade->pnl >= 0 ? '+' : '-' }}${{ number_format(abs($trade->pnl), 2) }}
                                            </span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Closed At:</span>
                                    <span class="detail-value">{{ $trade->closed_at }} {{ $trade->is_auto_close ? '(Auto-Closed)' : '' }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Trading Mode:</span>
                                    <span class="detail-value">{{ ucfirst($trade->trading_mode) }}</span>
                                </div>
                            </div>

                            <div class="cta-section">
                                <a href="{{ route('user.dashboard') }}" class="button">View Trade History</a>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/chart.png" alt="Chart Icon">
                                    What Happens Next?
                                </h3>
                                <ul>
                                    <li>The Amount Invested plus your Profit/Loss has been automatically credited back to your {{ ucfirst($trade->trading_mode) }} trading balance.</li>
                                    <li>This trade is now marked as Closed and is available in your trade history.</li>
                                    @if ($trade->is_auto_close)
                                        <li>This trade was automatically closed because it reached its expiry time.</li>
                                    @endif
                                </ul>
                            </div>

                            <div class="support-text">
                                <p><strong>Questions about this transaction?</strong> Contact support.</p>
                                <p><a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a></p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>You received this email because your trade closed on our platform.</p>
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
