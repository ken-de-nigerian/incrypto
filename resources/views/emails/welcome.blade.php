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
                background: #f1f5f9;
                color: #1a202c;
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
                max-width: 600px;
            }
            img {
                border: 0;
                outline: none;
                text-decoration: none;
                -ms-interpolation-mode: bicubic;
                max-width: 100%;
                height: auto;
            }
            a {
                text-decoration: none;
                color: inherit;
            }
            .email-wrapper {
                width: 100%;
                background: #f1f5f9;
                padding: 20px 0;
            }
            .container {
                width: 100%;
                max-width: 600px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
            }
            .header {
                background: #000000;
                padding: 30px;
                text-align: center;
            }
            .logo-container {
                text-align: center;
            }
            .logo-img {
                max-width: 150px;
                height: auto;
                display: block;
                margin: 0 auto 10px;
            }
            .welcome-badge {
                display: inline-block;
                background: rgba(255, 255, 255, 0.9);
                color: #1a202c;
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
            }
            .content {
                padding: 30px 20px;
            }
            .greeting {
                font-size: 28px;
                font-weight: bold;
                margin: 0 0 10px;
                color: #000000;
                text-align: center;
            }
            .subtitle {
                font-size: 16px;
                color: #6b7280;
                margin: 0 0 30px;
                text-align: center;
                line-height: 1.5;
            }
            .feature-grid {
                display: table;
                width: 100%;
                margin: 30px 0;
            }
            .feature-row {
                display: table-row;
            }
            .feature-item {
                display: table-cell;
                width: 50%;
                padding: 15px;
                vertical-align: top;
            }
            .feature-icon {
                margin-bottom: 10px;
                text-align: center;
                height: 40px; /* Set fixed height for alignment */
                line-height: 40px; /* Vertically center icon */
            }
            .feature-title {
                font-size: 16px;
                font-weight: 600;
                color: #000000;
                margin: 10px 0 5px;
                text-align: center;
            }
            .feature-desc {
                font-size: 14px;
                color: #6b7280;
                text-align: center;
                line-height: 1.4;
            }
            .cta-section {
                margin: 30px 0;
                text-align: center;
                padding: 25px;
                background: #f3f4f6;
                border-radius: 8px;
            }
            .cta-title {
                font-size: 18px;
                font-weight: 600;
                color: #000000;
                margin: 0 0 15px;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background: #000000;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: bold;
                font-size: 16px;
            }
            .info-box {
                background: #f0f9ff;
                border: 1px solid #e0f2fe;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                text-align: left;
            }
            .info-box h3 {
                margin: 0 0 10px;
                color: #0369a1;
                font-size: 16px;
            }
            .info-box ul {
                margin: 0;
                padding-left: 20px;
                color: #374151;
                font-size: 14px;
            }
            .info-box li {
                margin: 5px 0;
            }
            .support-text {
                background: #f3f4f6;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                text-align: center;
            }
            .support-text p {
                margin: 0 0 8px;
                color: #374151;
                font-size: 14px;
            }
            .support-email {
                color: #1f2937 !important;
                font-weight: bold;
                text-decoration: none;
            }
            .footer {
                background: #f8fafc;
                padding: 20px;
                text-align: center;
                border-top: 1px solid #e2e8f0;
            }
            .footer p {
                margin: 4px 0;
                font-size: 12px;
                color: #6b7280;
            }
            .social-links {
                margin: 15px 0 0;
            }
            .social-link {
                display: inline-block;
                margin: 0 6px;
                text-decoration: none;
            }
            .social-img {
                width: 24px;
                height: 24px;
                vertical-align: middle;
            }
            /* Media Queries for Responsiveness */
            @media only screen and (max-width: 600px) {
                .email-wrapper {
                    padding: 10px;
                }
                .container {
                    width: 100% !important;
                    border-radius: 0;
                    border-left: 0;
                    border-right: 0;
                }
                .header {
                    padding: 20px;
                }
                .logo-img {
                    max-width: 120px;
                }
                .content {
                    padding: 20px 15px;
                }
                .greeting {
                    font-size: 24px;
                }
                .subtitle {
                    font-size: 14px;
                    margin: 0 0 20px;
                }
                .feature-item {
                    display: block;
                    width: 100% !important;
                    padding: 15px 0;
                }
                .cta-section {
                    padding: 20px 15px;
                }
                .button {
                    padding: 10px 20px;
                    font-size: 14px;
                    display: block;
                    width: auto;
                    max-width: 200px;
                    margin: 0 auto;
                }
                .info-box {
                    padding: 12px;
                    margin: 15px 0;
                }
                .info-box h3 {
                    font-size: 14px;
                }
                .info-box ul {
                    font-size: 12px;
                }
                .support-text {
                    padding: 10px;
                    margin: 15px 0;
                }
                .support-text p {
                    font-size: 12px;
                }
                .footer {
                    padding: 15px;
                }
                .footer p {
                    font-size: 11px;
                }
                .social-img {
                    width: 20px;
                    height: 20px;
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
                                <div class="welcome-badge">
                                    Welcome Aboard!
                                </div>
                            </div>
                        </div>

                        <div class="content">
                            <h1 class="greeting">Welcome to {{ config('app.name') }}!</h1>

                            <p class="subtitle">Your secure crypto wallet is ready. Start managing your digital assets with confidence and ease.</p>

                            <div class="feature-grid">
                                <div class="feature-row">
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <img src="https://img.icons8.com/ios-glyphs/40/000000/lock-2.png" alt="Security Icon" width="40" height="40">
                                        </div>
                                        <div class="feature-title">Bank-Grade Security</div>
                                        <div class="feature-desc">Your assets are protected with advanced encryption and multi-layer security</div>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <img src="https://img.icons8.com/ios-glyphs/40/000000/lightning-bolt.png" alt="Transfers Icon" width="40" height="40">
                                        </div>
                                        <div class="feature-title">Instant Transfers</div>
                                        <div class="feature-desc">Send and receive crypto instantly with minimal transaction fees</div>
                                    </div>
                                </div>
                                <div class="feature-row">
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <img src="https://img.icons8.com/ios-glyphs/40/000000/bar-chart.png" alt="Analytics Icon" width="40" height="40">
                                        </div>
                                        <div class="feature-title">Real-Time Analytics</div>
                                        <div class="feature-desc">Track your portfolio performance with live market data and insights</div>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <img src="https://img.icons8.com/ios-glyphs/40/000000/globe-earth.png" alt="Currency Icon" width="40" height="40">
                                        </div>
                                        <div class="feature-title">Multi-Currency Support</div>
                                        <div class="feature-desc">Manage Bitcoin, Ethereum, and 100+ other cryptocurrencies in one place</div>
                                    </div>
                                </div>
                            </div>

                            <div class="cta-section">
                                <p class="cta-title">Ready to get started?</p>
                                <a href="{{ route('user.dashboard') }}" class="button">Open Your Wallet</a>
                            </div>

                            <div class="info-box">
                                <h3>
                                    <img src="https://img.icons8.com/ios-glyphs/24/0369a1/shield.png" alt="Security Shield Icon" style="vertical-align: -6px; margin-right: 8px; width:24px; height:24px;">Security Best Practices:
                                </h3>
                                <ul>
                                    <li><strong>Enable 2FA:</strong> Add an extra layer of security to your account</li>
                                    <li><strong>Secure Your Recovery Phrase:</strong> Store it offline in a safe location</li>
                                    <li><strong>Verify Addresses:</strong> Always double-check recipient addresses before sending</li>
                                    <li><strong>Stay Alert:</strong> We'll never ask for your password or recovery phrase via email</li>
                                    <li><strong>Keep Software Updated:</strong> Use the latest version of our app for optimal security</li>
                                </ul>
                            </div>

                            <div class="support-text">
                                <p><strong>Need help getting started?</strong></p>
                                <p>Our support team is available 24/7 to assist you. Visit our Help Center or reach out at <a href="mailto:{{ config('settings.site.site_email') }}" class="support-email">{{ config('settings.site.site_email') }}</a></p>
                                <p style="margin-top: 12px; font-size: 13px; color: #6b7280;">You can also check out our comprehensive guides and video tutorials in the app.</p>
                            </div>
                        </div>

                        <div class="footer">
                            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p>Building the future of digital finance</p>
                            <p style="margin-top: 10px; font-size: 11px;">This email was sent to you because you created an account with {{ config('app.name') }}</p>
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
