@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Balance Adjustment Notification - {{ config('app.name') }}</title>
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
                background: linear-gradient(135deg, #1f2a44 0%, #334155 100%);
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
            .badge.credit {
                background-color: #dcfce7;
                color: #166534;
            }
            .badge.debit {
                background-color: #fee2e2;
                color: #991b1b;
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
            .amount-highlight {
                font-size: 18px;
                font-weight: 700;
                color: #1f2a44;
            }
            .amount-highlight.credit {
                color: #166534;
            }
            .amount-highlight.debit {
                color: #991b1b;
            }
            .info-box {
                border-radius: 8px;
                padding: 20px;
                margin: 32px 0;
                text-align: left;
            }
            .info-box.credit {
                background: #dcfce7;
                border-left: 4px solid #16a34a;
            }
            .info-box.debit {
                background: #fee2e2;
                border-left: 4px solid #dc2626;
            }
            .info-box h3 {
                margin: 0 0 12px;
                font-size: 17px;
                display: flex;
                align-items: center;
            }
            .info-box.credit h3 {
                color: #166534;
            }
            .info-box.debit h3 {
                color: #991b1b;
            }
            .info-box h3 img {
                width: 24px;
                height: 24px;
                margin-right: 12px;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                font-size: 14px;
            }
            .info-box.credit ul {
                color: #166534;
            }
            .info-box.debit ul {
                color: #991b1b;
            }
            .info-box li {
                margin: 6px 0;
                line-height: 1.5;
            }
            .reason-box {
                background-color: #f0f9ff;
                border-left: 4px solid #0369a1;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: left;
            }
            .reason-box h4 {
                margin: 0 0 8px;
                color: #0369a1;
                font-size: 15px;
            }
            .reason-box p {
                margin: 0;
                color: #0c4a6e;
                font-size: 14px;
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
                .details-table .label {
                    font-weight: 700;
                }
                .details-table .value {
                    font-size: 16px;
                    font-weight: 600;
                    padding-top: 4px;
                }
                .details-table .icon {
                    display: none;
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
                            <div class="badge {{ $action_type }}">
                                {{ $action_type === 'credit' ? 'Balance Credited' : 'Balance Debited' }}
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">
                                @if($action_type === 'credit')
                                    Credit Notification
                                @else
                                    Debit Notification
                                @endif
                            </h1>

                            @if($action_type === 'credit')
                                <p class="subtitle">Hello {{ $user->first_name }}, your account has been successfully credited with cryptocurrency.</p>
                            @else
                                <p class="subtitle">Hello {{ $user->first_name }}, your account has been successfully debited with cryptocurrency.</p>
                            @endif

                            <div class="details-card">
                                <table class="details-table" role="presentation">
                                    <tr>
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/475569/bitcoin.png" alt="" class="icon">
                                            <span class="label">Token</span>
                                        </td>
                                        <td>
                                            <span class="value">{{ $token }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/475569/coins.png" alt="" class="icon">
                                            <span class="label">Amount</span>
                                        </td>
                                        <td>
                                            <span class="value amount-highlight {{ $action_type }}">
                                                @if($action_type === 'debit')
                                                    -
                                                @else
                                                    +
                                                @endif
                                                {{ $amount }} {{ $token }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/475569/time.png" alt="" class="icon">
                                            <span class="label">Transaction Time</span>
                                        </td>
                                        <td>
                                            <span class="value">{{ Carbon::now()->setTimezone('Africa/Lagos')->format('F j, Y, g:i A') }} (WAT)</span>
                                        </td>
                                    </tr>
                                    @if($action_type === 'credit')
                                        <tr>
                                            <td class="label-cell">
                                                <img src="https://img.icons8.com/material-rounded/24/475569/checkmark.png" alt="" class="icon">
                                                <span class="label">Status</span>
                                            </td>
                                            <td>
                                                <span class="value" style="color: #166534; font-weight: 600;">Completed</span>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            @if($reason)
                                <div class="reason-box">
                                    <h4>Transaction Reason</h4>
                                    <p>{{ $reason }}</p>
                                </div>
                            @endif

                            <div class="info-box {{ $action_type }}">
                                <h3>
                                    @if($action_type === 'credit')
                                        <img src="https://img.icons8.com/fluency-systems-filled/48/166534/checked.png" alt="Check Icon">
                                        Account Updated
                                    @else
                                        <img src="https://img.icons8.com/fluency-systems-filled/48/991b1b/info.png" alt="Info Icon">
                                        Important Notice
                                    @endif
                                </h3>
                                <ul>
                                    @if($action_type === 'credit')
                                        <li>Your account balance has been increased and is immediately available.</li>
                                        <li>The credited amount is now reflected in your wallet balance.</li>
                                    @else
                                        <li>Your account balance has been decreased as requested.</li>
                                        <li>The debited amount has been processed from your wallet.</li>
                                    @endif
                                    <li>For your security, we notify you of all balance adjustments.</li>
                                    <li>Please contact support if you did not authorize this transaction.</li>
                                </ul>
                            </div>

                            <div class="button-container">
                                <a href="{{ route('user.dashboard') }}" class="button">Go to My Dashboard</a>
                            </div>

                            <div class="support-text">
                                <p>
                                    @if($action_type === 'debit')
                                        If you did not authorize this debit, please contact our support team immediately at
                                    @else
                                        If you have any questions about this credit, please contact us at
                                    @endif
                                    <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.
                                </p>
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
