<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Export Ready</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .download-btn {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .download-btn:hover {
            background-color: #218838;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Your Export is Ready!</h1>
    </div>

    <div class="content">
        <h2>Hi Admin,</h2>

        <p>Great news! Your user export has been processed successfully and is now ready for download.</p>

        <div class="info-box">
            <strong>Export Details:</strong><br>
            📁 Format: {{ $format }}<br>
            📊 File: {{ $filename }}<br>
            ⏰ Generated: {{ now()->format('F j, Y \a\t g:i A') }}
        </div>

        <div style="text-align: center;">
            <a href="{{ $downloadUrl }}" class="download-btn">
                📥 Download Export File
            </a>
        </div>

        <p><strong>Important Notes:</strong></p>
        <ul>
            <li>This file will be available for download for the next 7 days</li>
            <li>The export includes all users matching your selected filters</li>
            <li>If you have any issues downloading, please contact support</li>
        </ul>

        <p>Thank you for using our user management system!</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
