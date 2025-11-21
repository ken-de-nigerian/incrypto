@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Copy Trade Closed - {{ config('app.name') }}</title>
        <style>
            /* --- RESET & BASE STYLES --- */
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
                padding: 8px 20px;
                border-radius: 9999px;
                font-size: 14px;
                font-weight: 700;
            }

            .badge-profit {
                background-color: #dcfce7;
                color: #166534;
            }

            .badge-loss {
                background-color: #fee2e2;
                color: #991b1b;
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

            .pnl-highlight {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                margin: 24px 0;
                text-align: center;
            }

            .pnl-label {
                font-size: 14px;
                color: #64748b;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 8px;
            }

            .pnl-amount {
                font-size: 36px;
                font-weight: 800;
                margin: 8px 0;
                line-height: 1;
            }

            .pnl-profit {
                color: #16a34a;
            }

            .pnl-loss {
                color: #ef4444;
            }

            .pnl-description {
                font-size: 13px;
                color: #64748b;
                margin-top: 8px;
            }

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

            .separator {
                border-top: 1px solid #e2e8f0;
                margin: 10px 0;
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
            }

            @media only screen and (max-width: 640px) {
                .email-wrapper {
                    padding: 0;
                }

                .container {
                    border-radius: 0;
                    border: 0;
                }

                .content {
                    padding: 24px 16px;
                }

                .greeting {
                    font-size: 22px;
                }

                .pnl-amount {
                    font-size: 28px;
                }

                .button {
                    width: 100%;
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
                                <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }} Logo"
                                     class="logo-img">
                            </a>
                            <div class="badge {{ $pnl >= 0 ? 'badge-profit' : 'badge-loss' }}">
                                Trade Closed - {{ $pnl >= 0 ? 'Profit' : 'Loss' }}
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Copy Trade Closed</h1>
                            <p class="subtitle">Hi {{ $user->first_name }}, your copy trade on <strong>{{ $pairName }}</strong>
                                has been closed automatically.</p>

                            <!-- PnL Highlight Section -->
                            <div class="pnl-highlight">
                                <div class="pnl-label">Final Result</div>
                                <div class="pnl-amount {{ $pnl >= 0 ? 'pnl-profit' : 'pnl-loss' }}">
                                    {{ $pnlFormatted }}
                                </div>
                                <div class="pnl-description">
                                    Your {{ ucfirst($transaction->type) }} trade on {{ $pairName }} resulted in
                                    a {{ $pnl >= 0 ? 'profit' : 'loss' }}
                                </div>
                            </div>

                            <!-- Trade Details -->
                            <div class="trade-details-box">
                                <div class="detail-row">
                                    <span class="detail-label">Asset Pair:</span>
                                    <span class="detail-value">{{ $pairName }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Trade Type:</span>
                                    <span class="detail-value"
                                          style="color: {{ strtolower($transaction->type) === 'up' || strtolower($transaction->type) === 'buy' ? '#16a34a' : '#ef4444' }};">
                                                        {{ ucfirst($transaction->type) }}
                                                    </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Trade Amount:</span>
                                    <span class="detail-value">${{ number_format($transaction->amount, 2) }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Entry Price:</span>
                                    <span class="detail-value">{{ number_format($metadata['entry_price'] ?? 0, 5) }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Exit Price:</span>
                                    <span class="detail-value">{{ $exitPrice }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Duration:</span>
                                    <span class="detail-value">{{ $metadata['duration'] ?? 'N/A' }}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">Closed At:</span>
                                    <span class="detail-value">{{ Carbon::parse($closedAt)->format('M d, Y h:i A') }}</span>
                                </div>

                                <div class="separator"></div>

                                <div class="detail-row">
                                    <span class="detail-label">Profit/Loss:</span>
                                    <span class="detail-value" style="color: {{ $pnl >= 0 ? '#16a34a' : '#ef4444' }};">
                                        {{ $pnlFormatted }}
                                    </span>
                                </div>
                            </div>

                            <div class="cta-section">
                                <a href="{{ route('user.trade.network.copied') }}" class="button">View Trade History</a>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/fluency-systems-filled/48/0369a1/info.png" alt="Info Icon">
                                    What Happens Next?
                                </h3>
                                <ul>
                                    <li>{{ $pnl >= 0 ? 'Your profit has been added to your account balance.' : 'This trade result has been reflected in your account balance.' }}</li>
                                    <li>You are still actively copying your Master Trader for future trades.</li>
                                    <li>You can review all your copy trading history in your dashboard.</li>
                                    <li>To stop copying this trader, visit your Copy Trading settings.</li>
                                </ul>
                            </div>

                            <div class="support-text">
                                <p><strong>Questions about this trade?</strong> Our support team is here to help.</p>
                                <p><a href="mailto:{{ config('settings.site.site_email') }}"
                                      class="support-email">{{ config('settings.site.site_email') }}</a></p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>You received this email because you have active copy trading enabled.</p>
                            <div class="social-links">
                                <a href="{{ config('settings.social.site_fb') }}" class="social-link" title="Facebook">
                                    <img src="https://img.icons8.com/fluency/48/facebook-new.png" alt="Facebook"
                                         class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_instagram') }}" class="social-link" title="Instagram">
                                    <img src="https://img.icons8.com/fluency/48/instagram-new.png" alt="Instagram"
                                         class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_linkedin') }}" class="social-link" title="LinkedIn">
                                    <img src="https://img.icons8.com/fluency/48/linkedin.png" alt="LinkedIn" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_youtube') }}" class="social-link" title="YouTube">
                                    <img src="https://img.icons8.com/fluency/48/youtube-play.png" alt="YouTube"
                                         class="social-img">
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
