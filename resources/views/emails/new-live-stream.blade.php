<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Live Stream</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .live-badge {
            display: inline-block;
            background-color: #ff4444;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            animation: pulse 2s infinite;
        }
        .upcoming-badge {
            display: inline-block;
            background-color: #ffa500;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .banner-container {
            width: 100%;
            max-height: 300px;
            overflow: hidden;
            background-color: #e9ecef;
        }
        .banner-img {
            width: 100%;
            height: auto;
            display: block;
        }
        .content {
            padding: 30px 20px;
        }
        .stream-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .stream-description {
            color: #666;
            margin: 15px 0;
            line-height: 1.8;
        }
        .info-row {
            display: flex;
            align-items: center;
            margin: 12px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .info-row span {
            margin-right: 8px;
            font-size: 18px;
        }
        .price-tag {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }
        .free-tag {
            display: inline-block;
            background-color: #17a2b8;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }
        .watch-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            transition: transform 0.2s;
        }
        .watch-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
        }
        .cta-section {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 14px;
            padding: 30px 20px;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎥 {{ $isLive ? 'New Live Stream!' : 'Upcoming Stream' }}</h1>
            @if($isLive)
                <span class="live-badge">🔴 LIVE NOW</span>
            @else
                <span class="upcoming-badge">📅 SCHEDULED</span>
            @endif
        </div>

        @if($bannerUrl)
        <div class="banner-container">
            <img src="{{ $bannerUrl }}" alt="{{ $streamTitle }}" class="banner-img">
        </div>
        @endif

        <div class="content">
            <h2 class="stream-title">{{ $streamTitle }}</h2>

            <div class="info-row">
                <span>👤</span>
                <strong>Hosted by:</strong> {{ $streamerName }}
            </div>

            @if(!$isLive && $scheduledAt)
            <div class="info-row">
                <span>📅</span>
                <strong>Scheduled for:</strong> {{ $scheduledAt->format('F j, Y \a\t g:i A') }}
            </div>
            @endif

            @if($streamDescription)
            <div class="stream-description">
                <strong>About this stream:</strong><br>
                {{ $streamDescription }}
            </div>
            @endif

            <div style="margin: 20px 0;">
                @if($isFree)
                    <span class="free-tag">🎉 FREE TO WATCH</span>
                @else
                    <span class="price-tag">💰 {{ $currency }} {{ number_format($price, 2) }}</span>
                    @if($freeMinutes > 0)
                        <div class="info-row" style="margin-top: 10px;">
                            <span>⏱️</span>
                            <strong>First {{ $freeMinutes }} minutes FREE!</strong>
                        </div>
                    @endif
                @endif
            </div>

            <div class="cta-section">
                <p style="font-size: 18px; margin: 0 0 15px 0;">
                    @if($isLive)
                        <strong>Join thousands of viewers watching live right now!</strong>
                    @else
                        <strong>Don't miss this exciting stream!</strong>
                    @endif
                </p>
                <a href="{{ $streamUrl }}" class="watch-btn">
                    {{ $isLive ? '🔴 Watch Now' : '🔔 Set Reminder' }}
                </a>
            </div>

            @if($isLive)
            <div style="text-align: center; margin: 20px 0; padding: 15px; background-color: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                <strong>⚡ Stream is live now!</strong> Click the button above to start watching immediately.
            </div>
            @endif
        </div>

        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                You're receiving this email because you've enabled notifications for new live streams.
            </p>
            <p style="margin: 0 0 20px 0; font-size: 12px;">
                <a href="{{ url('/settings/notifications') }}">Manage notification preferences</a>
            </p>

            <div class="social-links">
                <a href="{{ url('/') }}">🏠 Visit Website</a> |
                <a href="{{ url('/streams') }}">📺 Browse Streams</a> |
                <a href="{{ url('/support') }}">💬 Support</a>
            </div>

            <p style="margin-top: 20px; color: #999; font-size: 12px;">
                © {{ date('Y') }} Connect Inc. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
