<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin OTP Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #A20030, #D63865);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .otp-section {
            text-align: center;
            margin: 30px 0;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #A20030;
        }

        .otp-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #A20030;
            letter-spacing: 8px;
            margin: 0;
            font-family: 'Courier New', monospace;
        }

        .otp-note {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
        }

        .info-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }

        .info-box h3 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 16px;
        }

        .info-box p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }

        .security-tips {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }

        .security-tips h3 {
            margin: 0 0 15px 0;
            color: #721c24;
            font-size: 16px;
        }

        .security-tips ul {
            margin: 0;
            padding-left: 20px;
            color: #721c24;
        }

        .security-tips li {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }

        .footer p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }

        .footer .company-info {
            margin-top: 15px;
            font-size: 12px;
            color: #adb5bd;
        }

        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }

            .header, .content, .footer {
                padding: 20px;
            }

            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Admin Security Verification</h1>
            <p>ConnectApp Admin Portal</p>
        </div>

        <div class="content">
            <h2>Hello {{ $admin->name }},</h2>

            <p>You're attempting to access the ConnectApp Admin Portal. For security purposes, please use the verification code below to complete your login.</p>

            <div class="otp-section">
                <div class="otp-label">Your Verification Code</div>
                <p class="otp-code">{{ $otp }}</p>
                <div class="otp-note">This code expires in 10 minutes</div>
            </div>

            <div class="info-box">
                <h3>📋 What you need to do:</h3>
                <p>Enter this 6-digit code in the verification page to access your admin dashboard. The code is valid for the next 10 minutes.</p>
            </div>

            <div class="security-tips">
                <h3>🔒 Security Reminders:</h3>
                <ul>
                    <li>Never share your verification code with anyone</li>
                    <li>ConnectApp staff will never ask for your verification code</li>
                    <li>If you didn't request this code, please contact support immediately</li>
                    <li>This verification is required every 24 hours for enhanced security</li>
                </ul>
            </div>

            <p><strong>Login Details:</strong></p>
            <ul>
                <li><strong>Time:</strong> {{ now()->format('F j, Y g:i A T') }}</li>
                <li><strong>IP Address:</strong> {{ request()->ip() }}</li>
                <li><strong>User Agent:</strong> {{ request()->userAgent() }}</li>
            </ul>

            <p>If this wasn't you, please secure your account immediately and contact our support team.</p>

            <p>Best regards,<br>
            <strong>ConnectApp Security Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated message from ConnectApp Admin Portal.</p>
            <div class="company-info">
                © {{ date('Y') }} ConnectApp. All rights reserved.<br>
                This email was sent to {{ $admin->email }} for admin authentication purposes.
            </div>
        </div>
    </div>
</body>
</html>
