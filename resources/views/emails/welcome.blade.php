<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Connect Inc. App</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #A20030 0%, #D63865 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .greeting {
            font-size: 20px;
            color: #A20030;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .message {
            color: #333333;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #A20030 0%, #D63865 100%);
            color: #ffffff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            box-shadow: 0 3px 6px rgba(162, 0, 48, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(162, 0, 48, 0.4);
        }
        .features {
            background-color: #f8f9fa;
            padding: 30px;
            margin: 30px 0;
            border-radius: 8px;
            border-left: 4px solid #A20030;
        }
        .features h3 {
            color: #A20030;
            margin-top: 0;
            font-size: 18px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .feature-list li {
            padding: 8px 0;
            color: #333333;
        }
        .feature-list li:before {
            content: "✓";
            color: #A20030;
            font-weight: bold;
            margin-right: 10px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-text {
            color: #666666;
            font-size: 14px;
            margin: 0;
        }
        .company-name {
            color: #A20030;
            font-weight: bold;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #A20030;
            text-decoration: none;
            font-size: 12px;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }
            .header {
                padding: 30px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .features {
                padding: 20px;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="{{ url('/images/connect_logo.png') }}" alt="Connect Inc. App Logo" class="logo">
            <h1>Welcome to Connect Inc. App!</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Hi {{ $user->name }},</div>

            <div class="message">
                🎉 <strong>Welcome to our amazing community!</strong><br><br>
                Thank you for joining <span class="company-name">Connect Inc. App</span>. We're absolutely thrilled to have you on board and can't wait for you to explore all the incredible features we've built just for you.
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/') }}" class="cta-button">Explore Your Account</a>
            </div>

            <div class="features">
                <h3>🚀 What you can do now:</h3>
                <ul class="feature-list">
                    <li>Connect with amazing people in your area</li>
                    <li>Share your thoughts and experiences</li>
                    <li>Discover interesting content from the community</li>
                    <li>Build meaningful relationships</li>
                    <li>Stay updated with real-time notifications</li>
                </ul>
            </div>

            <div class="message">
                Feel free to explore and connect with other users. Our community is here to support you every step of the way. If you have any questions or need assistance, please don't hesitate to reach out to our friendly support team.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Thanks for choosing <span class="company-name">Connect Inc. App</span><br>
                Making connections that matter.
            </p>

            <div class="social-links">
                <a href="#">Privacy Policy</a> |
                <a href="#">Terms of Service</a> |
                <a href="#">Support</a>
            </div>

            <p class="footer-text" style="font-size: 12px; margin-top: 20px;">
                © {{ date('Y') }} Connect Inc. App. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
