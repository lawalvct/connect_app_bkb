<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Message</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .email-header .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .email-body {
            padding: 40px 30px;
            color: #333333;
        }
        .email-body h2 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 20px;
        }
        .sender-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .sender-info strong {
            color: #667eea;
            font-size: 18px;
        }
        .message-preview {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #e9ecef;
            font-style: italic;
            color: #555;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
        .emoji {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="icon">💬</div>
            <h1>New Message!</h1>
        </div>

        <div class="email-body">
            <h2>Hi {{ $receiver->name }}! 👋</h2>

            <p><strong>{{ $sender->name }}</strong> sent you a message on <strong>Connect</strong>!</p>

            <div class="sender-info">
                <strong>{{ $sender->name }}</strong>
                <p style="margin: 10px 0 0 0; color: #666;">
                    @if($messageType === 'image')
                        <span class="emoji">📷</span> Sent you a photo
                    @elseif($messageType === 'video')
                        <span class="emoji">🎥</span> Sent you a video
                    @elseif($messageType === 'audio')
                        <span class="emoji">🎵</span> Sent you an audio message
                    @elseif($messageType === 'file')
                        <span class="emoji">📎</span> Sent you a file
                    @elseif($messageType === 'location')
                        <span class="emoji">📍</span> Shared their location
                    @else
                        Sent you a message
                    @endif
                </p>
            </div>

            @if($messageType === 'text' && $messagePreview)
            <div class="message-preview">
                "{{ Str::limit($messagePreview, 150) }}"
            </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $appUrl }}/conversations/{{ $conversationId }}" class="cta-button">
                    💬 Reply Now
                </a>
            </div>

            <p style="color: #666; font-size: 14px;">
                Don't keep {{ $sender->name }} waiting! Click the button above to read and reply to their message.
            </p>

            <div class="divider"></div>

            <p style="color: #999; font-size: 13px;">
                💡 <strong>Tip:</strong> Quick replies help you build stronger connections!
            </p>
        </div>

        <div class="footer">
            <p><strong>Connect</strong> - Where meaningful connections happen</p>
            <p>
                <a href="{{ $appUrl }}/settings/notifications">Manage Notification Settings</a> •
                <a href="{{ $appUrl }}">Open App</a>
            </p>
            <p style="margin-top: 20px;">
                If you didn't expect this email, you can safely ignore it.
            </p>
        </div>
    </div>
</body>
</html>
