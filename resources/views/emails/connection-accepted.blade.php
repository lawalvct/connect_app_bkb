<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Accepted!</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-wrapper {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .emoji {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .content {
            padding: 30px;
        }
        .profile-section {
            text-align: center;
            margin-bottom: 25px;
        }
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #10b981;
            margin-bottom: 15px;
        }
        .profile-name {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin: 0 0 5px 0;
        }
        .celebration-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            text-align: center;
            border: 2px solid #10b981;
        }
        .celebration-box h2 {
            margin: 0 0 10px 0;
            color: #059669;
            font-size: 20px;
        }
        .celebration-box p {
            margin: 0;
            color: #065f46;
            font-size: 16px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: scale(1.05);
        }
        .button-container {
            text-align: center;
            margin: 25px 0;
        }
        .features {
            margin: 25px 0;
            padding: 0;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin: 12px 0;
            color: #555;
        }
        .feature-icon {
            margin-right: 12px;
            font-size: 20px;
        }
        .footer {
            background-color: #f8f9fc;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            color: #888;
            font-size: 13px;
        }
        .footer a {
            color: #10b981;
            text-decoration: none;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #10b981;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
        .next-steps {
            background-color: #f8f9fc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                padding: 10px;
            }
            .content {
                padding: 20px;
            }
            .header {
                padding: 25px 15px;
            }
            .cta-button {
                padding: 12px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-wrapper">
            <!-- Header -->
            <div class="header">
                <div class="emoji">🎉</div>
                <h1>Connection Accepted!</h1>
            </div>

            <!-- Content -->
            <div class="content">
                <p style="color: #555; font-size: 16px;">Great news, <strong>{{ $sender->name ?? 'there' }}</strong>!</p>

                <div class="celebration-box">
                    <h2>🌟 You're Now Connected!</h2>
                    <p><strong>{{ $accepter->name ?? 'Someone' }}</strong> accepted your connection request</p>
                </div>

                <div class="profile-section">
                    @if($accepter->profile_url)
                        <img src="{{ $accepter->profile_url }}" alt="{{ $accepter->name }}" class="profile-image">
                    @else
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <span style="color: white; font-size: 40px; font-weight: 600;">{{ substr($accepter->name ?? 'U', 0, 1) }}</span>
                        </div>
                    @endif
                    <p class="profile-name">{{ $accepter->name ?? 'Your New Connection' }}</p>
                </div>

                <div class="next-steps">
                    <h3>🚀 What you can do now:</h3>
                    <div class="feature-item">
                        <span class="feature-icon">💬</span>
                        <span>Start a conversation and say hello!</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📞</span>
                        <span>Make voice or video calls</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📸</span>
                        <span>Share photos and stories</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🎬</span>
                        <span>Watch live streams together</span>
                    </div>
                </div>

                <div class="button-container">
                    <a href="{{ $appUrl }}/conversations" class="cta-button">Start Chatting</a>
                </div>

                <div class="divider"></div>

                <p style="color: #888; font-size: 14px; text-align: center;">
                    Break the ice! Send {{ $accepter->name ?? 'them' }} a message and start building your connection.
                </p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="social-links">
                    <a href="#">Facebook</a> |
                    <a href="#">Twitter</a> |
                    <a href="#">Instagram</a>
                </div>
                <p>You're receiving this email because you have an account on Connect.</p>
                <p>
                    <a href="{{ $appUrl }}/settings/notifications">Manage notification preferences</a> |
                    <a href="{{ $appUrl }}">Visit Connect</a>
                </p>
                <p style="margin-top: 15px;">© {{ date('Y') }} Connect Inc. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
