<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Connection Request</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border: 4px solid #667eea;
            margin-bottom: 15px;
        }
        .profile-name {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin: 0 0 5px 0;
        }
        .message-box {
            background-color: #f8f9fc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .message-box p {
            margin: 0;
            color: #555;
            font-size: 16px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
            text-decoration: none;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #667eea;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
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
                <div class="emoji">💫</div>
                <h1>New Connection Request!</h1>
            </div>

            <!-- Content -->
            <div class="content">
                <p style="color: #555; font-size: 16px;">Hi <strong>{{ $receiver->name ?? 'there' }}</strong>,</p>

                <div class="profile-section">
                    @if($sender->profile_url)
                        <img src="{{ $sender->profile_url }}" alt="{{ $sender->name }}" class="profile-image">
                    @else
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <span style="color: white; font-size: 40px; font-weight: 600;">{{ substr($sender->name ?? 'U', 0, 1) }}</span>
                        </div>
                    @endif
                    <p class="profile-name">{{ $sender->name ?? 'Someone' }}</p>
                </div>

                <div class="message-box">
                    <p>🎉 <strong>{{ $sender->name ?? 'Someone' }}</strong> wants to connect with you!</p>
                </div>

                <div class="features">
                    <div class="feature-item">
                        <span class="feature-icon">✨</span>
                        <span>Check out their profile and decide if you want to connect</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">💬</span>
                        <span>If you both connect, you can start chatting instantly</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📞</span>
                        <span>Make voice & video calls with your connections</span>
                    </div>
                </div>

                <div class="button-container">
                    <a href="{{ $appUrl }}/connections/requests" class="cta-button">View Request</a>
                </div>

                <div class="divider"></div>

                <p style="color: #888; font-size: 14px; text-align: center;">
                    Don't keep {{ $sender->name ?? 'them' }} waiting! Log in to respond to their request.
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
