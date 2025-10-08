<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Welcome to {{ config('app.name') }}</title>
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
            .welcome-badge {
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
            }
            .greeting {
                font-size: 28px;
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
                margin: 0 auto 24px;
            }
            .feature-grid {
                display: table;
                width: 100%;
                margin: 32px 0;
            }
            .feature-row {
                display: table-row;
            }
            .feature-item {
                display: table-cell;
                width: 50%;
                padding: 16px;
                vertical-align: top;
                background: #f8fafc;
                border-radius: 8px;
                margin: 4px;
            }
            .feature-title {
                font-size: 16px;
                font-weight: 600;
                color: #1f2a44;
                margin: 8px 0 4px;
                text-align: center;
            }
            .feature-desc {
                font-size: 14px;
                color: #64748b;
                text-align: center;
                line-height: 1.5;
            }
            .cta-section {
                margin: 32px 0;
                text-align: center;
                padding: 24px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
            }
            .cta-title {
                font-size: 18px;
                font-weight: 600;
                color: #1f2a44;
                margin: 0 0 16px;
            }
            .button {
                display: inline-block;
                padding: 14px 32px;
                background: #1f2a44;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: background-color 0.2s ease;
            }
            .button:hover {
                background: #2d3748;
            }
            .info-box {
                background: #f0f9ff;
                border: 1px solid #e0f2fe;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
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
                vertical-align: middle;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #1f2a44;
                font-size: 15px;
            }
            .info-box li {
                margin: 6px 0;
            }
            .support-text {
                background: #f3f4f6;
                border-radius: 8px;
                padding: 20px;
                margin: 24px 0;
                text-align: center;
            }
            .support-text p {
                margin: 0 0 8px;
                color: #1f2a44;
                font-size: 15px;
            }
            .support-email {
                color: #1f2a44 !important;
                font-weight: 600;
                text-decoration: underline;
            }
            .support-email:hover {
                color: #dc2626 !important;
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
                .welcome-badge {
                    padding: 6px 16px;
                    font-size: 13px;
                }
                .content {
                    padding: 24px 16px;
                }
                .greeting {
                    font-size: 24px;
                }
                .subtitle {
                    font-size: 14px;
                    margin: 0 0 16px;
                }
                .feature-item {
                    display: block;
                    width: 100%;
                    padding: 16px;
                    margin: 8px 0;
                }
                .cta-section {
                    padding: 20px 16px;
                    margin: 24px 0;
                }
                .cta-title {
                    font-size: 16px;
                }
                .button {
                    padding: 12px 24px;
                    font-size: 15px;
                    width: auto;
                    max-width: 100%;
                }
                .info-box {
                    padding: 16px;
                    margin: 16px 0;
                }
                .info-box h3 {
                    font-size: 15px;
                }
                .info-box h3 img {
                    width: 20px;
                    height: 20px;
                }
                .info-box ul {
                    font-size: 14px;
                    padding-left: 15px;
                }
                .support-text {
                    padding: 16px;
                    margin: 16px 0;
                }
                .support-text p {
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
                                <div class="welcome-badge">Welcome Aboard!</div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Welcome to {{ config('app.name') }}!</h1>
                            <p class="subtitle">Your secure crypto wallet is ready. Start managing your digital assets with confidence and ease.</p>

                            <div class="feature-grid">
                                <div class="feature-row">
                                    <div class="feature-item">
                                        <div class="feature-title">Bank-Grade Security</div>
                                        <div class="feature-desc">Your assets are protected with advanced encryption and multi-layer security.</div>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-title">Instant Transfers</div>
                                        <div class="feature-desc">Send and receive crypto instantly with minimal transaction fees.</div>
                                    </div>
                                </div>
                                <div class="feature-row">
                                    <div class="feature-item">
                                        <div class="feature-title">Real-Time Analytics</div>
                                        <div class="feature-desc">Track your portfolio performance with live market data and insights.</div>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-title">Multi-Currency Support</div>
                                        <div class="feature-desc">Manage Bitcoin, Ethereum, and 100+ other cryptocurrencies in one place.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="cta-section">
                                <p class="cta-title">Ready to get started?</p>
                                <a href="{{ route('user.dashboard') }}" class="button">Open Your Wallet</a>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/ios-glyphs/24/0369a1/shield.png" alt="Security Shield Icon">
                                    Security Best Practices
                                </h3>
                                <ul>
                                    <li><strong>Enable 2FA:</strong> Add an extra layer of security to your account.</li>
                                    <li><strong>Secure Your Recovery Phrase:</strong> Store it offline in a safe location.</li>
                                    <li><strong>Verify Addresses:</strong> Always double-check recipient addresses before sending.</li>
                                    <li><strong>Stay Alert:</strong> We'll never ask for your password or recovery phrase via email.</li>
                                    <li><strong>Keep Software Updated:</strong> Use the latest version of our app for optimal security.</li>
                                </ul>
                            </div>

                            <div class="support-text">
                                <p><strong>Need help getting started?</strong></p>
                                <p>Our support team is available 24/7 to assist you. Visit our Help Center or reach out at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a>.</p>
                                <p style="margin-top: 12px; font-size: 14px; color: #64748b;">You can also check out our comprehensive guides and video tutorials in the app.</p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>Building the future of digital finance</p>
                            <p style="margin-top: 10px; font-size: 12px;">This email was sent to you because you created an account with {{ config('app.name') }}.</p>
                            <div class="social-links">
                                <a href="{{ config('settings.social.site_fb') }}" class="social-link" title="Facebook">
                                    <img src="https://img.icons8.com/color/28/000000/facebook-new.png" alt="Facebook" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_instagram') }}" class="social-link" title="Instagram">
                                    <img src="https://img.icons8.com/color/28/000000/instagram.png" alt="Instagram" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_linkedin') }}" class="social-link" title="LinkedIn">
                                    <img src="https://img.icons8.com/color/28/000000/linkedin.png" alt="LinkedIn" class="social-img">
                                </a>
                                <a href="{{ config('settings.social.site_youtube') }}" class="social-link" title="YouTube">
                                    <img src="https://img.icons8.com/color/28/000000/youtube-play.png" alt="YouTube" class="social-img">
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
