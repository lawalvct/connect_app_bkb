<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - Connect Inc. App</title>
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
        .security-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
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
        .otp-container {
            background: linear-gradient(135deg, #f5c6cb 0%, #f8f9fa 100%);
            border: 2px solid #A20030;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        .otp-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #A20030 0%, #D63865 100%);
        }
        .otp-label {
            color: #A20030;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #A20030;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            background-color: #ffffff;
            padding: 15px 20px;
            border-radius: 8px;
            border: 1px solid #D63865;
            display: inline-block;
            min-width: 280px;
        }
        .security-notice {
            background-color: #f8f9fa;
            border-left: 4px solid #A20030;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        .security-notice h3 {
            color: #A20030;
            margin-top: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
        }
        .security-notice h3::before {
            content: "🛡️";
            margin-right: 8px;
        }
        .security-notice p {
            margin: 8px 0 0 0;
            color: #666666;
            font-size: 14px;
        }
        .expiry-warning {
            background-color: #f5c6cb;
            color: #A20030;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
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
        .support-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .support-info p {
            margin: 0;
            color: #666666;
            font-size: 14px;
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
            .otp-container {
                padding: 20px;
                margin: 20px 0;
            }
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
                min-width: 200px;
            }
            .security-notice {
                padding: 15px;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="https://connect.udemics.com/images/connect_logo.png" alt="Connect Inc. App Logo" class="logo">
            <div class="security-icon">
                🔐
            </div>
            <h1>Verify Your Email Address</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Hi {{ $user->name }},</div>

            <div class="message">
                Thank you for registering with <span class="company-name">Connect Inc. App</span>! 🎉<br><br>
                To complete your registration and secure your account, please use the verification code below. This extra step helps us ensure your account remains safe and protected.
            </div>

            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="expiry-warning">
                ⏰ This code will expire in 60 minutes
            </div>

            <div class="security-notice">
                <h3>Security Notice</h3>
                <p>
                    • Never share this code with anyone<br>
                    • We will never ask for this code via phone or email<br>
                    • If you didn't create an account, please ignore this email
                </p>
            </div>

            <div class="message">
                Once verified, you'll have full access to all the amazing features of <span class="company-name">Connect Inc. App</span>, including connecting with friends, sharing moments, and discovering new content.
            </div>

            <div class="support-info">
                <p>
                    <strong>Need help?</strong> If you're having trouble with verification or didn't request this code,
                    please contact our support team. We're here to help!
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Thanks for choosing <span class="company-name">Connect Inc. App</span><br>
                Making connections that matter.
            </p>

            <p class="footer-text" style="font-size: 12px; margin-top: 20px;">
                © {{ date('Y') }} Connect Inc. App. All rights reserved.<br>
                This is an automated message, please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
